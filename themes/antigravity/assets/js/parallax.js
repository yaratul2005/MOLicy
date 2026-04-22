/**
 * Parallax Depth Layers
 */

document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header');
    
    window.addEventListener('scroll', () => {
        const scrollY = window.scrollY;
        
        // Header backdrop blur logic
        if (scrollY > 50) {
            header.setAttribute('data-scrolled', 'true');
        } else {
            header.setAttribute('data-scrolled', 'false');
        }

        // Parallax elements
        document.querySelectorAll('.parallax').forEach(elem => {
            const speed = elem.getAttribute('data-speed') || 0.5;
            elem.style.transform = `translateY(${scrollY * speed}px)`;
        });
    }, { passive: true });
});
