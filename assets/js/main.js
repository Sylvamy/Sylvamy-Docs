document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const item = header.closest('.accordion-item');
            const isOpen = item.classList.contains('open');

            document.querySelectorAll('.accordion-item').forEach(i => i.classList.remove('open'));

            if (!isOpen) {
                item.classList.add('open');
            }
        });
    });

    if (window.hljs) {
        hljs.highlightAll();
    }

    document.querySelectorAll('pre > code').forEach(codeEl => {
        const pre = codeEl.parentElement;
        const wrapper = document.createElement('div');
        wrapper.className = 'code-block';
        pre.parentNode.insertBefore(wrapper, pre);
        wrapper.appendChild(pre);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'code-copy-btn';
        btn.setAttribute('aria-label', 'Copy code');
        btn.innerHTML = '<i class="fa-regular fa-copy"></i><span>Copy</span>';
        wrapper.appendChild(btn);

        btn.addEventListener('click', async () => {
            const text = codeEl.innerText;

            try {
                await navigator.clipboard.writeText(text);
                btn.classList.add('copied');
                btn.innerHTML = '<i class="fa-solid fa-check"></i><span>Copied</span>';

                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="fa-regular fa-copy"></i><span>Copy</span>';
                }, 1500);
            } catch (e) {
                console.error('Copy failed', e);
                btn.innerHTML = '<i class="fa-solid fa-xmark"></i><span>Error</span>';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="fa-regular fa-copy"></i><span>Copy</span>';
                }, 1500);
            }
        });
    });

    const navHeight = 80;
    document.querySelectorAll('.toc-link[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const id = link.getAttribute('href').substring(1);
            const target = document.getElementById(id);
            if (!target) return;

            const targetY = target.getBoundingClientRect().top + window.pageYOffset - navHeight;

            window.scrollTo({
                top: targetY,
                behavior: 'smooth'
            });
        });
    });

    const scrollBtn = document.querySelector('.scroll-top-btn');
    const showAt = 200;

    if (scrollBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > showAt) {
                scrollBtn.classList.add('visible');
            } else {
                scrollBtn.classList.remove('visible');
            }
        });

        scrollBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    const leftSidebar  = document.querySelector('.sidebar-left');
    const rightSidebar = document.querySelector('.sidebar-right');
    const leftToggle   = document.querySelector('.sidebar-toggle-left');
    const rightToggle  = document.querySelector('.sidebar-toggle-right');

    function closeRightSidebar() {
        if (!rightSidebar || !rightToggle) return;
        rightSidebar.classList.remove('open');
        const icon = rightToggle.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-angle-right');
            icon.classList.add('fa-angle-left');
        }
    }

    function closeLeftSidebar() {
        if (!leftSidebar || !leftToggle) return;
        leftSidebar.classList.remove('open');
        const icon = leftToggle.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-angle-left');
            icon.classList.add('fa-angle-right');
        }
    }

    if (leftToggle && leftSidebar) {
        leftToggle.addEventListener('click', () => {
            const icon = leftToggle.querySelector('i');
            const willOpen = !leftSidebar.classList.contains('open');

            if (willOpen) {
                leftSidebar.classList.add('open');
                closeRightSidebar();
                if (icon) {
                    icon.classList.remove('fa-angle-right');
                    icon.classList.add('fa-angle-left');
                }
            } else {
                leftSidebar.classList.remove('open');
                if (icon) {
                    icon.classList.remove('fa-angle-left');
                    icon.classList.add('fa-angle-right');
                }
            }
        });
    }

    if (rightToggle && rightSidebar) {
        rightToggle.addEventListener('click', () => {
            const icon = rightToggle.querySelector('i');
            const willOpen = !rightSidebar.classList.contains('open');

            if (willOpen) {
                rightSidebar.classList.add('open');
                closeLeftSidebar();
                if (icon) {
                    icon.classList.remove('fa-angle-left');
                    icon.classList.add('fa-angle-right');
                }
            } else {
                rightSidebar.classList.remove('open');
                if (icon) {
                    icon.classList.remove('fa-angle-right');
                    icon.classList.add('fa-angle-left');
                }
            }
        });
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1100) {
            if (leftSidebar) leftSidebar.classList.remove('open');
            if (rightSidebar) rightSidebar.classList.remove('open');
        }
    });
});
