<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
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
    </style>
</head>
<body>
<div class="container">
    <h2 class="my-4">Register</h2>
    <form id="register-form" action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="cpf">CPF:</label>
            <input type="text" class="form-control" id="cpf" name="cpf" required>
        </div>
        <div class="form-group">
            <label for="photo">Photo:</label>
            <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
        </div>
        <div class="camera">
            <button type="button" class="btn btn-primary" id="start-camera">Tirar Foto</button>
            <div class="mt-3">
                <video id="video" width="640" height="480" autoplay></video>
            </div>
            <button type="button" class="btn btn-success mt-3" id="capture">Capturar</button>
            <canvas id="canvas" width="640" height="480" style="display:none;"></canvas>
        </div>
        <input type="hidden" name="captured_image" id="captured_image">
        <button type="submit" class="btn btn-primary mt-3">Register</button>
    </form>
</div>

<script>
    document.getElementById('start-camera').addEventListener('click', async function() {
        const video = document.getElementById('video');
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
    });

    document.getElementById('capture').addEventListener('click', function() {
        const canvas = document.getElementById('canvas');
        const video = document.getElementById('video');
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const dataURL = canvas.toDataURL('image/png');
        document.getElementById('captured_image').value = dataURL;
        alert('Foto capturada e pronta para envio!');
    });

    document.getElementById('register-form').addEventListener('submit', function(event) {
        const capturedImage = document.getElementById('captured_image').value;
        const photoInput = document.getElementById('photo');
        if (capturedImage && !photoInput.files.length) {
            const blob = dataURItoBlob(capturedImage);
            const file = new File([blob], 'captured_image.png', { type: 'image/png' });
            const container = new DataTransfer();
            container.items.add(file);
            photoInput.files = container.files;
        }
    });

    function dataURItoBlob(dataURI) {
        const byteString = atob(dataURI.split(',')[1]);
        const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
        const ab = new ArrayBuffer(byteString.length);
        const ia = new Uint8Array(ab);
        for (let i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        return new Blob([ab], { type: mimeString });
    }
</script>
</body>
</html>
