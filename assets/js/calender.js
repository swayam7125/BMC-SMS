/**
 * @fileoverview Professional, well-structured script for a dynamic calendar component.
 * This script handles rendering, navigation, and date selection for a monthly calendar.
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- DOM Element References ---
    const monthYearElement = document.getElementById('month-year');
    const calendarGrid = document.getElementById('calendar-grid');
    const prevMonthButton = document.getElementById('prev-month');
    const nextMonthButton = document.getElementById('next-month');

    // --- State ---
    let currentDate = new Date(); // Controls the month being displayed
    let selectedDate = null;      // Stores the user-selected date

    // --- Constants ---
    const CSS_CLASSES = {
        DAY: 'calendar-day',
        OTHER_MONTH: 'other-month',
        TODAY: 'today',
        PAST: 'past-date',
        SELECTED: 'selected-date', // Class for the selected date
    };

    /**
     * Creates a DOM element for a single day in the calendar.
     * @param {number} day - The day number to display.
     * @param {string} fullDate - The full date string (e.g., "2025-07-22").
     * @param {string[]} [classes=[]] - An array of additional CSS classes.
     * @returns {HTMLElement} The created day element.
     */
    function createDayElement(day, fullDate, classes = []) {
        const dayElement = document.createElement('div');
        dayElement.classList.add(CSS_CLASSES.DAY, ...classes);
        dayElement.textContent = day;
        // Store the full date for easy retrieval on click
        if (!classes.includes(CSS_CLASSES.OTHER_MONTH)) {
            dayElement.dataset.date = fullDate;
        }
        return dayElement;
    }

    /**
     * Renders the entire calendar for the month specified in `currentDate`.
     */
    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        monthYearElement.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;
        calendarGrid.innerHTML = '';

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        // 1. Add days from the previous month.
        for (let i = firstDayOfMonth; i > 0; i--) {
            const day = prevMonthLastDay - i + 1;
            calendarGrid.appendChild(createDayElement(day, null, [CSS_CLASSES.OTHER_MONTH]));
        }

        // 2. Add all days for the current month.
        for (let day = 1; day <= daysInMonth; day++) {
            const dayClasses = [];
            const currentDayDate = new Date(year, month, day);
            const fullDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            if (currentDayDate < today) {
                dayClasses.push(CSS_CLASSES.PAST);
            }
            if (currentDayDate.getTime() === today.getTime()) {
                dayClasses.push(CSS_CLASSES.TODAY);
            }
            // Check if this day is the selected date.
            if (selectedDate && currentDayDate.getTime() === selectedDate.getTime()) {
                dayClasses.push(CSS_CLASSES.SELECTED);
            }

            calendarGrid.appendChild(createDayElement(day, fullDateStr, dayClasses));
        }

        // 3. Add days from the next month.
        const totalDaysRendered = firstDayOfMonth + daysInMonth;
        const nextMonthDays = (7 - (totalDaysRendered % 7)) % 7;
        for (let day = 1; day <= nextMonthDays; day++) {
            calendarGrid.appendChild(createDayElement(day, null, [CSS_CLASSES.OTHER_MONTH]));
        }
    }

    /**
     * Handles clicks on the calendar grid using event delegation.
     * @param {Event} e - The click event object.
     */
    function handleDateSelection(e) {
        const target = e.target;
        // Check if a valid day was clicked (must have a date dataset).
        if (target.dataset.date) {
            selectedDate = new Date(target.dataset.date);
            // Adjust for timezone offset to prevent date from being off by one.
            selectedDate.setMinutes(selectedDate.getMinutes() + selectedDate.getTimezoneOffset());
            renderCalendar(); // Re-render to show the new selection.
        }
    }
    
    function navigateMonths(direction) {
        currentDate.setMonth(currentDate.getMonth() + direction);
        renderCalendar();
    }

    // --- Event Listeners ---
    calendarGrid.addEventListener('click', handleDateSelection);
    prevMonthButton.addEventListener('click', () => navigateMonths(-1));
    nextMonthButton.addEventListener('click', () => navigateMonths(1));

    // --- Initial Render ---
    renderCalendar();
});
