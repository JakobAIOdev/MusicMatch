const notificationQueue = [];
let isNotificationVisible = false;

function showNotification(message, type = 'success', duration = 3000) {
    notificationQueue.push({ message, type, duration });
    if (!isNotificationVisible){
        processNextNotification();
    }
}
function processNextNotification() {
    if (notificationQueue.length === 0) {
        isNotificationVisible = false;
        return;
    }
    
    isNotificationVisible = true;
    const { message, type, duration } = notificationQueue.shift();
    document.querySelectorAll('.mm-notification-hiding').forEach(notification => {
        notification.remove();
    });
    
    const notification = document.createElement('div');
    notification.className = `mm-notification mm-notification-${type}`;
    
    const iconPath = getNotificationIconPath(type);
    
    notification.innerHTML = `
        <div class="mm-notification-icon">
            <img src="${iconPath}" alt="${type} icon">
        </div>
        <div class="mm-notification-message">${message}</div>
        <button class="mm-notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    const style = document.createElement('style');
    style.textContent = `
        .mm-notification::after { 
            animation-duration: ${duration}ms;
        }
    `;
    notification.appendChild(style);
    
    notification.querySelector('.mm-notification-close').addEventListener('click', () => {
        hideNotification(notification);
        setTimeout(processNextNotification, 300);
    });
    
    setTimeout(() => notification.classList.add('mm-notification-visible'), 10);
    
    if (duration > 0) {
        setTimeout(() => {
            if (document.body.contains(notification)) {
                hideNotification(notification);
                setTimeout(processNextNotification, 300);
            }
        }, duration);
    }
}

function hideNotification(notification) {
    notification.classList.add('mm-notification-hiding');
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.remove();
        }
    }, 300);
}

function getNotificationIconPath(type) {
    switch(type) {
        case 'success':
            return './assets/img/icons/success.svg';
        case 'error':
            return './assets/img/icons/error.svg';
        default: // info
            return './assets/img/icons/info.svg';
    }
}