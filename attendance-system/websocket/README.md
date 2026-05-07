# WebSocket Server for Attendance System

This WebSocket server provides real-time updates for the attendance system.

## Installation

First, install the required dependencies:

```bash
npm install
```

## Running the Server

### Option 1: Using npm
```bash
npm start
```

### Option 2: Using Node.js directly
```bash
node server.js
```

### Option 3: Using startup scripts
- **Windows**: Double-click `start.bat` or run it from command prompt
- **Linux/Mac**: Run `./start.sh` or `bash start.sh`

The server will run on `ws://localhost:8080`

## Features

- Real-time attendance data broadcasting
- Automatic data fetching from PHP API (`/api/services.php?resource=attendances`)
- Client connection/disconnection tracking
- Broadcasts attendance data to all connected clients

## Connected Clients

The following files connect to this WebSocket server:

- `admin/dashboard.php` - Admin dashboard for real-time updates
- `admin/attendance.php` - Attendance logs page  
- `Identification.cs` - C# biometric client application

## Server Behavior

1. When a client connects, the server immediately fetches the latest attendance data
2. The server broadcasts attendance data to all connected clients
3. Client messages are logged to the console
4. Connection/disconnection events are tracked and logged

## Troubleshooting

- **Port 8080 already in use**: Make sure no other application is using port 8080
- **Cannot connect**: Ensure the PHP API is accessible at `http://localhost/attendance-system/api/services.php`
- **Module not found**: Run `npm install` to install dependencies

