<!DOCTYPE html>
<html>
<head>
    <title>Face Recognition</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        video, canvas {
            display: block;
            margin: 0 auto;
        }
        .camera {
            text-align: center;
            margin-top: 20px;
        }
        #canvas-overlay {
            position: absolute;
            top: 0;
            left: 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="my-4">Face Recognition</h2>
    <div class="camera">
        <button type="button" class="btn btn-primary" id="start-camera">Start Camera</button>
        <div class="mt-3 position-relative">
            <video id="video" width="640" height="480" autoplay></video>
            <canvas id="canvas-overlay" width="640" height="480"></canvas>
        </div>
    </div>
    <div id="results" class="mt-3"></div>
</div>

<script>
    const video = document.getElementById('video');
    const canvasOverlay = document.getElementById('canvas-overlay');
    const contextOverlay = canvasOverlay.getContext('2d');
    let intervalId;

    document.getElementById('start-camera').addEventListener('click', async function() {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;

        // Start continuous face recognition
        intervalId = setInterval(captureAndRecognize, 2000);
    });

    async function captureAndRecognize() {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataURL = canvas.toDataURL('image/png');

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('image', dataURL);

        const response = await fetch('{{ route('usuarios.consult') }}', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        contextOverlay.clearRect(0, 0, canvasOverlay.width, canvasOverlay.height);

        if (result.names && result.names.length > 0) {
            result.names.forEach(name => {
                contextOverlay.fillStyle = 'red';
                contextOverlay.font = '20px Arial';
                contextOverlay.fillText(name.name, name.x, name.y);
                contextOverlay.strokeRect(name.x, name.y, 100, 50); // Adjust the rectangle size as needed
            });
        } else {
            contextOverlay.fillStyle = 'rgba(0, 255, 0, 0.5)';
            contextOverlay.fillRect(0, 0, canvasOverlay.width, canvasOverlay.height);
            contextOverlay.fillStyle = 'black';
            contextOverlay.font = '30px Arial';
            contextOverlay.fillText('No face detected', canvasOverlay.width / 2 - 100, canvasOverlay.height / 2);
        }
    }

    function stopRecognition() {
        clearInterval(intervalId);
    }
</script>
</body>
</html>
