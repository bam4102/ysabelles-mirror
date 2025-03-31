document.addEventListener('DOMContentLoaded', function() {
    const uuid = localStorage.getItem('deviceUUID');
    const deviceUUIDInput = document.getElementById('deviceUUID');

    if (!uuid) {
      window.location.href = 'unauthorized.php';
      return;
    }

    deviceUUIDInput.value = uuid;

    fetch('./assets/controllers/check_device.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'uuid=' + encodeURIComponent(uuid)
      })
      .then(response => response.json())
      .then(data => {
        if (!data.exists) {
          window.location.href = 'unauthorized.php';
        }
      })
      .catch(error => {
        console.error('Error:', error);
      });
  });