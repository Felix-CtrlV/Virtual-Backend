

    <div class="about-hero reveal-box">
        <div class="about-content-wrapper">
            <span class="sub-title reveal-fade">// OUR STORY</span>
            <h1 class="reveal-up">
                Discover Our <br>
                <span class="highlight-white">Passion</span>
            </h1>
            <p class="reveal-up delay-1">
                WE SELL BEAUTIFUL DECOR ITEMS, FASHION PIECES AND MORE. <br>
                Bringing art and comfort into your living space.
            </p>
            <div class="about-stats reveal-up delay-2">
                <div class="stat-card">
                    <span class="stat-number">100%</span>
                    <p class="stat-label">Quality</p>
                </div>
                <div class="stat-card">
                    <span class="stat-number">24/7</span>
                    <p class="stat-label">Support</p>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
    const reveal = () => {
        const elements = document.querySelectorAll('.reveal-up, .reveal-fade, .reveal-box');
        elements.forEach(el => {
            const elementTop = el.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            if (elementTop < windowHeight - 100) {
                el.classList.add('active');
            }
        });
    };
    window.addEventListener('scroll', reveal);
    window.addEventListener('load', reveal);
</script>