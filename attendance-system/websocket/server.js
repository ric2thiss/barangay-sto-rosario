const WebSocket = require('ws');
const config = require('./config');

const PORT = config.websocket.port;
const HOST = config.websocket.host;
const API_BASE_URL = config.api.baseUrl;
const ATTENDANCES_ENDPOINT = API_BASE_URL + config.api.endpoints.attendances;

const wss = new WebSocket.Server({ port: PORT, host: HOST });

console.log(`WebSocket Server running on ws://${HOST}:${PORT}`);

// Broadcast helper
async function broadcast(message) {
  try {
    // Fetch data only once
    const res = await fetch(ATTENDANCES_ENDPOINT);
    const data = await res.json();

    // Loop through connected clients
    for (const client of wss.clients) {
      if (client.readyState === WebSocket.OPEN) {
        if (message === null) {
          // Send attendance data only (no text message)
          client.send(JSON.stringify(data));
        } else {
          // Send text message (legacy behavior)
          client.send(message);
        }
      }
    }
  } catch (err) {
    console.error('❌ Error during broadcast:', err);
  }
}

wss.on('connection', (ws) => {
  // Mark connection as alive
  ws.isAlive = true;
  
  // Send initial attendance data to the new client
  broadcast(null); // null means just fetch and send data, not a text message

  ws.on('message', async (msg) => {
    try {
      const message = JSON.parse(msg.toString());

      // Handle ping (heartbeat) from clients
      if (message.type === 'ping') {
        // Respond with pong to keep connection alive
        if (ws.readyState === WebSocket.OPEN) {
          ws.send(JSON.stringify({ type: 'pong', timestamp: Date.now() }));
        }
        return;
      }

      // Handle attendance update from C# (contains full attendance data)
      if (message.type === 'attendance_update') {
        // Fetch fresh data from API to include correspondingAttendance
        // This ensures web clients get the complete data including the corresponding in/out pair
        try {
          const res = await fetch(ATTENDANCES_ENDPOINT);
          const freshData = await res.json();
          
          // Broadcast the fresh attendance data to all web clients (excluding the sender which is C#)
          // This includes correspondingAttendance which is needed for displaying both TIME IN and TIME OUT
          for (const client of wss.clients) {
            if (client.readyState === WebSocket.OPEN && client !== ws) {
              // Send the fresh data with correspondingAttendance included
              client.send(JSON.stringify({
                type: 'attendance_update',
                data: freshData
              }));
            }
          }
        } catch (err) {
          console.error('❌ Error fetching fresh attendance data:', err);
          // Fallback: forward the original message if API fetch fails
          for (const client of wss.clients) {
            if (client.readyState === WebSocket.OPEN && client !== ws) {
              client.send(JSON.stringify(message));
            }
          }
        }
      }
      
      // Handle attendance error from C# (contains error information)
      if (message.type === 'attendance_error') {
        // Broadcast the error message to all web clients (excluding the sender which is C#)
        for (const client of wss.clients) {
          if (client.readyState === WebSocket.OPEN && client !== ws) {
            // Forward the error message
            client.send(JSON.stringify(message));
          }
        }
      }
    } catch (err) {
      // If message is not JSON, it might be a text ping
      const msgText = msg.toString();
      if (msgText === 'ping' || msgText === 'PING') {
        if (ws.readyState === WebSocket.OPEN) {
          ws.send('pong');
        }
      }
    }
  });

  // Handle pong response (keepalive)
  ws.on('pong', () => {
    // Client is alive
    ws.isAlive = true;
  });

  ws.on('close', () => {
    // Client disconnected
  });
  
  // Handle errors
  ws.on('error', (error) => {
    console.error('❌ WebSocket error:', error);
  });
});

// Ping all clients every 30 seconds to check if they're alive
// If no pong received, terminate the connection
const pingInterval = setInterval(() => {
  wss.clients.forEach((ws) => {
    if (ws.isAlive === false) {
      return ws.terminate();
    }
    
    ws.isAlive = false;
    if (ws.readyState === WebSocket.OPEN) {
      ws.ping();
    }
  });
}, 30000);

// Clean up interval on server shutdown
process.on('SIGINT', () => {
  clearInterval(pingInterval);
  wss.close(() => {
    process.exit(0);
  });
});
