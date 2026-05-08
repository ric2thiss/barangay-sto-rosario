const video = document.getElementById('video');
const overlay = document.getElementById('overlay');
const logList = document.getElementById('logList');
const ctx = overlay.getContext('2d');

// Known faces
const labeledDescriptors = [
  { name: "Rich", id: 1, img: "assets/rich.jpg" },
  { name: "Ric",  id: 2, img: "assets/ric.png" }
];

let faceMatcher;
const logged = new Set();

async function loadLabeledImages() {
  return Promise.all(
    labeledDescriptors.map(async (person) => {
      const img = await faceapi.fetchImage(person.img);

      let detection = await faceapi
        .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ 
          inputSize: 320,    // higher = more accuracy, slower
          scoreThreshold: 0.5 // lower = detect more faces
        }))
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (!detection) {
        detection = await faceapi
          .detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
          .withFaceLandmarks()
          .withFaceDescriptor();
      }

      if (!detection) {
        alert(`⚠️ No face detected in ${person.img}. Use a clearer photo.`);
        return null;
      }

      return new faceapi.LabeledFaceDescriptors(person.name, [detection.descriptor]);
    })
  );
}

async function start() {
  await faceapi.nets.tinyFaceDetector.loadFromUri('models');
  await faceapi.nets.ssdMobilenetv1.loadFromUri('models');
  await faceapi.nets.faceLandmark68Net.loadFromUri('models');
  await faceapi.nets.faceRecognitionNet.loadFromUri('models');

  const labeledFaceDescriptors = await loadLabeledImages();
  const validDescriptors = labeledFaceDescriptors.filter(d => d !== null);

  faceMatcher = new faceapi.FaceMatcher(validDescriptors, 0.4);

  navigator.mediaDevices.getUserMedia({ video: {} }).then((stream) => {
    video.srcObject = stream;
  });
}

video.addEventListener('play', () => {
  const displaySize = { width: video.width, height: video.height };

  // Make sure canvas matches the video size
  faceapi.matchDimensions(overlay, displaySize);

  setInterval(async () => {
    const detections = await faceapi
      .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
        inputSize: 416,
        scoreThreshold: 0.6
      }))
      .withFaceLandmarks()
      .withFaceDescriptors();

    // Clear canvas
    ctx.clearRect(0, 0, overlay.width, overlay.height);

    // 🔑 Resize detections to match video dimensions
    const resizedDetections = faceapi.resizeResults(detections, displaySize);

    if (resizedDetections.length && faceMatcher) {
      const results = resizedDetections.map(d =>
        faceMatcher.findBestMatch(d.descriptor)
      );

      results.forEach((result, i) => {
        const { x, y, width, height } = resizedDetections[i].detection.box;

        // Draw bounding box on correct position
        ctx.strokeStyle = result.label === "unknown" ? "red" : "lime";
        ctx.lineWidth = 3;
        ctx.strokeRect(x, y, width, height);

        // Label
        ctx.fillStyle = result.label === "unknown" ? "red" : "lime";
        ctx.font = "16px Arial";
        ctx.fillText(result.toString(), x, y - 8);

        // if (result.label !== "unknown") {
        //   logAttendance(result.label);
        // }

        if (result.label !== "unknown") {
          const person = labeledDescriptors.find(p => p.name === result.label);
          if (person) {
            logAttendance(person.id, person.name);
          }
        }
      });
    }
  }, 500);
});


// function logAttendance(name) {
//   if (!logged.has(name)) {
//     logged.add(name);
//     const li = document.createElement("li");
//     li.textContent = `${name} - ${new Date().toLocaleTimeString()}`;
//     logList.appendChild(li);
//   }

//   fetch("./logbook.php",{
//     method: "POST",

//   })
// }

function logAttendance(id, name) {
  if (!logged.has(id)) {
    logged.add(id);
    const li = document.createElement("li");
    li.textContent = `${name} - ${new Date().toLocaleTimeString()}`;
    logList.appendChild(li);

    // ✅ Send data to PHP backend
    fetch("./logbook.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id,
        name
        // time: new Date().toISOString()
      })
    })
    .then(res => res.text())
    .then(data => console.log("✅ Saved:", data))
    .catch(err => console.error("❌ Error saving:", err));
  }
}

start();
