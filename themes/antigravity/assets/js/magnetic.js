/**
 * Magnetic Hover System
 * Pulls elements towards the cursor
 */

document.addEventListener('DOMContentLoaded', () => {
    const magneticElements = document.querySelectorAll('.btn, .magnetic');

    magneticElements.forEach(elem => {
        elem.addEventListener('mousemove', (e) => {
            const rect = elem.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            elem.style.transform = `translate(${x * 0.2}px, ${y * 0.2}px)`;
        });

        elem.addEventListener('mouseleave', () => {
            elem.style.transform = `translate(0px, 0px)`;
            // the CSS transition variable handles the spring-back
        });
    });
});
