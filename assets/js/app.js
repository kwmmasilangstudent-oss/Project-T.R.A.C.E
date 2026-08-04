document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.glass-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 80}ms`;
        card.classList.add('fade-in');
    });
});