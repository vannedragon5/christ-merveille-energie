document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    // Toggle menu
    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        hamburger.classList.toggle('active');
    });

    // Slider Gallery Logic
    let currentIndex = 0;
    let slides = [];

    function updateSlider() {
        const track = document.getElementById('gallery-images');
        if (!track || slides.length === 0 || !slides[0]) return;
        const slideWidth = slides[0].getBoundingClientRect().width;
        if (slideWidth === 0) {
            // Si la largeur est encore 0, on retente dans 100ms
            setTimeout(updateSlider, 100);
            return;
        }
        track.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
    }

    const nextButton = document.querySelector('.next-btn');
    const prevButton = document.querySelector('.prev-btn');

    if (nextButton) {
        nextButton.addEventListener('click', () => {
            if (slides.length === 0) return;
            const visibleSlides = getVisibleSlides();
            if (currentIndex < slides.length - visibleSlides) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateSlider();
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', () => {
            if (slides.length === 0) return;
            if (currentIndex > 0) {
                currentIndex--;
            } else {
                const visibleSlides = getVisibleSlides();
                currentIndex = Math.max(0, slides.length - visibleSlides);
            }
            updateSlider();
        });
    }

    function getVisibleSlides() {
        if (window.innerWidth >= 1024) return 3;
        if (window.innerWidth >= 768) return 2;
        return 1;
    }

    window.addEventListener('resize', () => {
        currentIndex = 0;
        updateSlider();
    });

    // Auto-slide
    setInterval(() => { if (nextButton) nextButton.click(); }, 5000);

    // Navigation links close menu
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
        });
    });

    // ── Formulaire de contact (AJAX) ──────────────────────────────
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const formNotif = document.getElementById('formNotif');

    function showNotif(msg, success) {
        formNotif.textContent = msg;
        formNotif.style.display = 'block';
        if (success) {
            formNotif.style.background = '#D1FAE5';
            formNotif.style.color = '#065F46';
            formNotif.style.border = '1px solid #6EE7B7';
        } else {
            formNotif.style.background = '#FEF2F2';
            formNotif.style.color = '#DC2626';
            formNotif.style.border = '1px solid #FCA5A5';
        }
        formNotif.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi en cours...';
            formNotif.style.display = 'none';

            const payload = {
                name: document.getElementById('name').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                message: document.getElementById('message').value.trim()
            };

            try {
                const res = await fetch('backend/messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showNotif(data.message || 'Message envoyé ! Nous vous répondrons très vite.', true);
                    contactForm.reset();
                } else {
                    showNotif(data.error || 'Une erreur est survenue.', false);
                }
            } catch {
                showNotif('Impossible d\'envoyer le message. Vérifiez votre connexion.', false);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Envoyer le message';
            }
        });
    }

    // ── Chargement du contenu dynamique ──────────────────────────
    function loadDynamicContent() {
        // Contenu texte générique
        fetch('backend/content.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const c = data.content;

                // Mapping des clés DB vers IDs DOM
                const mapping = {
                    'hero_titre': 'hero-title',
                    'hero_sous_titre': 'hero-subtitle',
                    'hero_description': 'hero-description',
                    'apropos_titre': 'apropos-titre',
                    'apropos_texte1': 'apropos-texte1',
                    'apropos_texte2': 'apropos-texte2',
                    'services_titre': 'services-titre',
                    'services_sous_titre': 'services-sous-titre'
                };

                for (const [key, id] of Object.entries(mapping)) {
                    if (c[key]) {
                        const el = document.getElementById(id);
                        if (el) {
                            // Si c'est un titre avec \n, on remplace par <br>
                            if (key === 'hero_titre' || key === 'apropos_titre') {
                                el.innerHTML = c[key].replace(/\n/g, '<br>');
                            } else {
                                el.textContent = c[key];
                            }
                        }
                    }
                }

                // WhatsApp
                if (c.whatsapp_numero) {
                    document.querySelectorAll('a[href*="wa.me"]').forEach(a => {
                        a.href = `https://wa.me/${c.whatsapp_numero}`;
                    });
                }

                // Téléphone
                if (c.telephone) {
                    document.querySelectorAll('.info-item h4').forEach(h4 => {
                        if (h4.textContent.toLowerCase().includes('appelez')) {
                            const p = h4.nextElementSibling;
                            if (p) p.textContent = c.telephone;
                        }
                    });
                }

                // Email
                if (c.email) {
                    document.querySelectorAll('.info-item h4').forEach(h4 => {
                        if (h4.textContent.toLowerCase().includes('email')) {
                            const p = h4.nextElementSibling;
                            if (p) p.textContent = c.email;
                        }
                    });
                }

                // Footer
                if (c.footer_texte) {
                    const fp = document.querySelector('.footer-brand p');
                    if (fp) fp.textContent = c.footer_texte;
                }
            })
            .catch(err => console.error('Erreur chargement contenu:', err));

        // Services
        fetch('backend/services.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.services) return;
                const grid = document.getElementById('services-grid');
                if (!grid) return;

                grid.innerHTML = '';
                data.services.forEach(s => {
                    const card = document.createElement('div');
                    card.className = 'service-card';
                    card.innerHTML = `
                        <div class="icon-box">
                            <i class="${s.icon || 'fas fa-bolt'}"></i>
                        </div>
                        <h3>${s.titre}</h3>
                        <p>${s.description}</p>
                        ${s.note ? `<br><p class="services-note">${s.note}</p>` : ''}
                    `;
                    grid.appendChild(card);
                });
            })
            .catch(err => console.error('Erreur chargement services:', err));
    }

    // ── Chargement de la galerie ──────────────────────────
    function loadGallery() {
        fetch('backend/upload.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.medias) return;

                const imgTrack = document.getElementById('gallery-images');
                const videoGrid = document.getElementById('gallery-videos');

                if (imgTrack) {
                    imgTrack.innerHTML = '';
                    const images = data.medias.filter(m => m.type === 'image');
                    images.forEach(m => {
                        const item = document.createElement('div');
                        item.className = 'gallery-item';
                        item.innerHTML = `<img src="${m.chemin}" alt="Réalisation Christ Merveille Energie">`;
                        imgTrack.appendChild(item);
                    });
                    // Mettre à jour la liste globale des slides pour le slider
                    slides = Array.from(imgTrack.children);
                }

                if (videoGrid) {
                    videoGrid.innerHTML = '';
                    const videos = data.medias.filter(m => m.type === 'video');
                    if (videos.length === 0) {
                        videoGrid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--muted)">Aucune vidéo disponible pour le moment.</p>';
                    } else {
                        videos.forEach(m => {
                            const item = document.createElement('div');
                            item.className = 'video-item';
                            item.innerHTML = `
                                <video controls>
                                    <source src="${m.chemin}" type="video/mp4">
                                    Votre navigateur ne supporte pas la lecture de vidéos.
                                </video>
                            `;
                            videoGrid.appendChild(item);
                        });
                    }
                }
            })
            .catch(err => console.error('Erreur chargement galerie:', err));
    }

    loadDynamicContent();
    loadGallery();
});

// Splash Screen Logic
window.addEventListener('load', () => {
    const splashScreen = document.getElementById('splash-screen');
    setTimeout(() => {
        splashScreen.classList.add('hidden');
        setTimeout(() => { splashScreen.style.display = 'none'; }, 500);
    }, 2000);
});

