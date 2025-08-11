document.addEventListener("DOMContentLoaded", function () {
  const notificationContainer = document.getElementById(
    "dashboard-notifications-list"
  );
  const base_path = "/BMC-SMS/";

  function getIconClass(type) {
    switch (type) {
      case "borrow_status":
        return "fas fa-book-reader";
      case "borrow_request":
        return "fas fa-hand-holding-hand";
      case "leave_request":
        return "fas fa-calendar-plus";
      case "new_notice":
        return "fas fa-file-alt";
      case "school_notice":
        return "fas fa-chalkboard-teacher";
      case "new_assignment":
        return "fas fa-file-signature";
      default:
        return "fas fa-bell";
    }
  }

  fetch(`${base_path}fetch_notifications.php`)
    .then((response) => response.json())
    .then((data) => {
      if (!notificationContainer) return;

      let allNotifications = [];
      for (const category in data) {
        allNotifications = allNotifications.concat(data[category]);
      }

      allNotifications.sort(
        (a, b) => new Date(b.raw_date) - new Date(a.raw_date)
      );
      const recentNotifications = allNotifications.slice(0, 5);

      notificationContainer.innerHTML = "";

      if (recentNotifications.length === 0) {
        notificationContainer.innerHTML = `
                        <div class="list-group-item text-center text-gray-500 py-4">
                            <div class="mb-2"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div>
                            All caught up! No notifications.
                        </div>`;
      } else {
        recentNotifications.forEach((notification) => {
          const bgClass = !notification.is_read ? "bg-light" : "";
          const iconBgClass = !notification.is_read
            ? "bg-primary"
            : "bg-primary";
          const fontWeightClass = !notification.is_read
            ? "font-weight-bold"
            : "";
          const textColorClass = !notification.is_read ? "" : "text-grey-500";
          const opacityClass = !notification.is_read
            ? "opacity : 1 ;"
            : "opacity : 0.85 ;";

          // Construct the final link with notif_id to mark as read
          const link = `${base_path}${notification.link.replace(/^\//, "")}`;
          const separator = link.includes("?") ? "&" : "?";
          const final_link = `${link}${separator}notif_id=...`; // Placeholder for notif_id which is missing

          const notificationHtml = `
                            <a href="${final_link}" class="list-group-item list-group-item-action d-flex align-items-center ${bgClass}">
                                <div class="mr-3">
                                    <div class="icon-circle ${iconBgClass}" style="height: 2.5rem; width: 2.5rem; border-radius: 100%; display: flex; align-items: center; justify-content: center; ${opacityClass}">
                                        <i class="${getIconClass(
                                          notification.type
                                        )} text-white"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="small ${textColorClass}">${
            notification.time_ago
          }</div>
                                    <span class="${fontWeightClass}">${
            notification.message
          }</span>
                                </div>
                            </a>`;
          notificationContainer.innerHTML += notificationHtml;
        });
      }
    })
    .catch((error) => {
      console.error("Error fetching notifications for dashboard:", error);
      if (notificationContainer) {
        notificationContainer.innerHTML =
          '<div class="list-group-item text-danger">Could not load notifications.</div>';
      }
    });
});
