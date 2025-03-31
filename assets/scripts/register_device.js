document.addEventListener('DOMContentLoaded', function() {
    let uuid = localStorage.getItem('deviceUUID');
    const messageDiv = document.getElementById('message');
    
    if (uuid) {
        messageDiv.textContent = `Device already registered (UUID: ${uuid})`;
        return;
    }

    uuid = crypto.randomUUID();
    localStorage.setItem('deviceUUID', uuid);

    fetch('./assets/controllers/save_device.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'uuid=' + encodeURIComponent(uuid)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageDiv.textContent = `Device registered successfully! UUID: ${uuid}`;
        } else {
            messageDiv.innerHTML = 'Registration failed. ' + (data.error || '');
            localStorage.removeItem('deviceUUID');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.textContent = 'An error occurred during registration.';
        localStorage.removeItem('deviceUUID');
    });
});