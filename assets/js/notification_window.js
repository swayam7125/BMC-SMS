document.addEventListener("DOMContentLoaded", function () {
    const notificationWindow = document.getElementById("notification-window-wrapper");
    if (!notificationWindow) return; // Exit if the element doesn't exist

    const tabsContainer = notificationWindow.querySelector(".notification-tabs");
    const bodyContainer = notificationWindow.querySelector(".notification-body");
    const baseUrl = document.getElementById('myAreaChart').dataset.baseUrl || '/BMC-SMS/';

    // Icons for each category
    const categoryIcons = {
        'Assignments': 'fas fa-tasks',
        'Leave Status': 'fas fa-calendar-check',
        'Notices': 'fas fa-bullhorn',
        'Timetables': 'fas fa-clock',
        'Other': 'fas fa-info-circle'
    };

    /**
     * Fetches and renders notifications.
     */
    async function fetchNotifications() {
        try {
            const response = await fetch(`${baseUrl}fetch_notifications.php`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.error) {
                bodyContainer.innerHTML = `<div class="notification-empty-state"><i class="fas fa-exclamation-circle"></i><p>${data.error}</p></div>`;
                return;
            }

            renderNotifications(data);

        } catch (error) {
            console.error("Error fetching notifications:", error);
            bodyContainer.innerHTML = `<div class="notification-empty-state"><i class="fas fa-exclamation-triangle"></i><p>Could not load notifications.</p></div>`;
        }
    }

    /**
     * Renders the fetched notifications into the DOM.
     * @param {object} categorizedData - The categorized notification data.
     */
    function renderNotifications(categorizedData) {
        tabsContainer.innerHTML = '';
        bodyContainer.innerHTML = '';
        let isFirstTab = true;

        for (const category in categorizedData) {
            const notifications = categorizedData[category];
            if (notifications.length === 0) continue; // Skip empty categories

            // Create Tab
            const tab = document.createElement('div');
            tab.className = 'notification-tab';
            tab.textContent = `${category} (${notifications.length})`;
            tab.dataset.category = category.replace(/\s+/g, '-').toLowerCase();
            tabsContainer.appendChild(tab);

            // Create Content Pane
            const content = document.createElement('div');
            content.id = `content-${category.replace(/\s+/g, '-').toLowerCase()}`;
            content.className = 'notification-content';
            
            const list = document.createElement('ul');
            list.className = 'notification-list';

            notifications.forEach(item => {
                const listItem = document.createElement('li');
                listItem.className = `notification-item ${item.is_read ? '' : 'unread'}`;
                
                const iconClass = category.replace(/\s+/g, '-').toLowerCase();
                
                listItem.innerHTML = `
                    <a href="${baseUrl}${item.link}">
                        <div class="notification-icon icon-${iconClass}">
                            <i class="${categoryIcons[category] || 'fas fa-bell'}"></i>
                        </div>
                        <div class="notification-details">
                            <p class="notification-message">${item.message}</p>
                            <span class="notification-time">${item.time_ago}</span>
                        </div>
                    </a>
                `;
                list.appendChild(listItem);
            });
            
            content.appendChild(list);
            bodyContainer.appendChild(content);

            if (isFirstTab) {
                tab.classList.add('active');
                content.classList.add('active');
                isFirstTab = false;
            }
        }

        if (isFirstTab) { // No notifications were found at all
             bodyContainer.innerHTML = `<div class="notification-empty-state"><i class="fas fa-bell-slash"></i><p>No new notifications.</p></div>`;
        }
    }

    // Event listener for tab switching
    tabsContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('notification-tab')) {
            // Deactivate all tabs and content
            tabsContainer.querySelectorAll('.notification-tab').forEach(tab => tab.classList.remove('active'));
            bodyContainer.querySelectorAll('.notification-content').forEach(content => content.classList.remove('active'));

            // Activate clicked tab and corresponding content
            e.target.classList.add('active');
            const categoryId = e.target.dataset.category;
            document.getElementById(`content-${categoryId}`).classList.add('active');
        }
    });

    // Initial fetch
    fetchNotifications();
});