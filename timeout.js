// Timeout session 
let time;
let interval; // Store interval ID for countdown

const resetTimer = () => {
    clearTimeout(time);
    // Set the timeout to 5 mins
    time = setTimeout(logout, 300000);
};

const logout = () => {
    Swal.fire({
        title: 'Are you still there?',
        html: 'You will be logged out in <strong id="countdown">10</strong> seconds.',
        icon: 'warning',
        timer: 10000,
        timerProgressBar: true,
        showConfirmButton: false,
        willOpen: () => {
            const countdownElement = Swal.getHtmlContainer().querySelector('#countdown');
            let countdown = 10;
            interval = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                if (countdown <= 0) {
                    clearInterval(interval);
                    window.location.href = 'logout.php'; // Redirect after countdown
                }
            }, 1000);
        },
        onClose: () => {
            clearInterval(interval);
        }
    });

    // Cancel the countdown if activity occurs after alert is shown
    const cancelCountdown = () => {
        clearInterval(interval);
        Swal.close();
        resetTimer(); // Reset timer on activity
        document.removeEventListener('mousemove', cancelCountdown);
        document.removeEventListener('keypress', cancelCountdown);
        document.removeEventListener('touchstart', cancelCountdown);
    };

    document.addEventListener('mousemove', cancelCountdown);
    document.addEventListener('keypress', cancelCountdown);
    document.addEventListener('touchstart', cancelCountdown);
};

// Initial call to reset the timer
resetTimer();

// Set event listeners for user activity
document.addEventListener('mousemove', resetTimer);
document.addEventListener('keypress', resetTimer);
document.addEventListener('touchstart', resetTimer);

