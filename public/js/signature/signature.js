document.addEventListener("DOMContentLoaded", function() {
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    canvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        [lastX, lastY] = [e.offsetX, e.offsetY];
    });

    canvas.addEventListener('mousemove', (e) => {
        if (!isDrawing) return;
        drawSignature(e.offsetX, e.offsetY);
    });

    canvas.addEventListener('mouseup', () => isDrawing = false);
    canvas.addEventListener('mouseout', () => isDrawing = false);

    document.getElementById('saveButton').addEventListener('click', saveSignature);
    document.getElementById('clearButton').addEventListener('click', clearSignature);

    function drawSignature(x, y) {
        if (!isDrawing) return;
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(x, y);
        ctx.strokeStyle = '#000';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.stroke();
        [lastX, lastY] = [x, y];
    }

    function saveSignature() {
        const dataURL = canvas.toDataURL(); // Get the image data as a Base64 string
        const inputField = document.getElementById('signature_image');
        inputField.value = dataURL; // Store the Base64 string in the hidden input field
        alert("Signature saved as Base64 and stored in the hidden input field!"); // Optional confirmation
    }

    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear the canvas
        const inputField = document.getElementById('signature_image');
        inputField.value = ""; // Clear the hidden input field
    }
});
