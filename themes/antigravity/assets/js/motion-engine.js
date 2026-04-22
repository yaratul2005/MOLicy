/**
 * AntiGravity Motion Engine
 * Handles IntersectionObserver for staggered animations
 */

document.addEventListener('DOMContentLoaded', () => {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                // Apply stagger delay based on index if multiple elements enter at once
                const delay = index * 100;
                
                setTimeout(() => {
                    entry.target.classList.add('animate-fall-in');
                    entry.target.style.opacity = 1;
                }, delay);

                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Elements to observe
    document.querySelectorAll('.thread-card, .category-card').forEach(el => {
        el.style.opacity = 0; // initial state
        observer.observe(el);
    });
});
