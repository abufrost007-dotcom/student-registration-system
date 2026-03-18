(() => {
    const root = document.documentElement;
    const toggle = document.querySelector("[data-theme-toggle]");
    const toggleKnob = document.querySelector("[data-theme-knob]");
    const buttons = document.querySelectorAll("[data-theme-value]");
    const csrf = document.body ? document.body.getAttribute("data-csrf") : "";
    const presetTheme = root.getAttribute("data-theme");
    const stored = localStorage.getItem("theme");
    const initialTheme = presetTheme || stored || "light-ocean";
    root.setAttribute("data-theme", initialTheme);
    localStorage.setItem("theme", initialTheme);

    const applyToggle = () => {
        const themeValue = root.getAttribute("data-theme") || "light-ocean";
        const isDark = themeValue.startsWith("dark");
        root.setAttribute("data-mode", isDark ? "dark" : "light");
        if (toggle) {
            toggle.setAttribute("aria-pressed", isDark ? "true" : "false");
        }
        if (toggleKnob) {
            toggleKnob.setAttribute("data-state", isDark ? "dark" : "light");
        }
    };

    applyToggle();

    if (toggle) {
        toggle.addEventListener("click", () => {
            const current = root.getAttribute("data-theme") || "light-ocean";
            const next = current.startsWith("dark") ? "light-ocean" : "dark-nebula";
            root.setAttribute("data-theme", next);
            localStorage.setItem("theme", next);
            savePreference({ theme: next });
            applyToggle();
        });
    }

    
    const savePreference = (payload) => {
        if (!csrf) return;
        fetch("save_preferences.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ ...payload, csrf_token: csrf })
        });
    };

    const attrName = (key) => `data-${key.replaceAll("_", "-")}`;

    const applyPreference = (key, value) => {
        if (!value) return;
        root.setAttribute(attrName(key), value);
        localStorage.setItem(key, value);
        savePreference({ [key]: value });
    };

    const prefs = ["density", "glass", "motion", "font", "art", "large_text", "minimal_mode"];
    prefs.forEach((key) => {
        const storedPref = localStorage.getItem(key);
        if (storedPref) {
            root.setAttribute(attrName(key), storedPref);
        }
    });

    const updateActive = (attr, value) => {
        document.querySelectorAll(`[${attr}]`).forEach((btn) => {
            const matches = btn.getAttribute(attr) === value;
            btn.classList.toggle("is-active", matches);
        });
    };

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => {
            const value = btn.getAttribute("data-theme-value");
            applyPreference("theme", value);
            updateActive("data-theme-value", value);
            applyToggle();
        });
    });

    document.querySelectorAll("[data-density]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const value = btn.getAttribute("data-density");
            applyPreference("density", value);
            updateActive("data-density", value);
        });
    });

    document.querySelectorAll("[data-glass]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const value = btn.getAttribute("data-glass");
            applyPreference("glass", value);
            updateActive("data-glass", value);
        });
    });

    document.querySelectorAll("[data-motion]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const value = btn.getAttribute("data-motion");
            applyPreference("motion", value);
            updateActive("data-motion", value);
        });
    });

    document.querySelectorAll("[data-font]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const value = btn.getAttribute("data-font");
            applyPreference("font", value);
            updateActive("data-font", value);
        });
    });

    const applyLocalPreference = (key, value) => {
        localStorage.setItem(key, value);
        if (key === "contrast") {
            root.setAttribute("data-contrast", value);
        }
        if (key === "focus") {
            document.body.classList.toggle("focus-mode", value === "on");
        }
    };

    applyLocalPreference("contrast", localStorage.getItem("contrast") || "off");
    applyLocalPreference("focus", localStorage.getItem("focus") || "off");

    const artRange = document.querySelector("[data-art-range]");
    if (artRange) {
        const presetArt = root.getAttribute("data-art");
        const storedArt = localStorage.getItem("art");
        const artValue = presetArt || storedArt;
        if (artValue) {
            artRange.value = artValue;
            root.style.setProperty("--art-opacity", Number(artValue) / 100);
        }
        artRange.addEventListener("input", (e) => {
            const value = e.target.value;
            root.style.setProperty("--art-opacity", value / 100);
            localStorage.setItem("art", value);
            savePreference({ art: value });
        });
    }

    updateActive("data-density", root.getAttribute("data-density") || "cozy");
    updateActive("data-glass", root.getAttribute("data-glass") || "on");
    updateActive("data-motion", root.getAttribute("data-motion") || "on");
    updateActive("data-font", root.getAttribute("data-font") || "jakarta");
    updateActive("data-theme-value", root.getAttribute("data-theme") || "light-ocean");
    document.querySelectorAll("[data-switch]").forEach((input) => {
        const key = input.getAttribute("data-switch");
        const onValue = input.getAttribute("data-on");
        const offValue = input.getAttribute("data-off");
        const stored = root.getAttribute(attrName(key)) || offValue;
        input.checked = stored === onValue;
        input.addEventListener("change", () => {
            const value = input.checked ? onValue : offValue;
            applyPreference(key, value);
            if (key === "theme") {
                updateActive("data-theme-value", value);
                applyToggle();
            }
        });
    });

    document.querySelectorAll("[data-local-switch]").forEach((input) => {
        const key = input.getAttribute("data-local-switch");
        const onValue = input.getAttribute("data-on");
        const offValue = input.getAttribute("data-off");
        const stored = localStorage.getItem(key) || offValue;
        input.checked = stored === onValue;
        input.addEventListener("change", () => {
            const value = input.checked ? onValue : offValue;
            applyLocalPreference(key, value);
        });
    });

    const revealers = document.querySelectorAll("[data-password-toggle]");
    revealers.forEach((btn) => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-password-toggle");
            const input = document.getElementById(targetId);
            if (!input) return;
            const isHidden = input.getAttribute("type") === "password";
            input.setAttribute("type", isHidden ? "text" : "password");
            btn.setAttribute("data-eye-state", isHidden ? "open" : "closed");
        });
    });

    const loginForm = document.querySelector("[data-login-form]");
    if (loginForm) {
        loginForm.addEventListener("submit", () => {
            document.body.classList.add("page-exit");
        });
    }

    const navToggle = document.querySelector("[data-nav-toggle]");
    if (navToggle) {
        navToggle.addEventListener("click", () => {
            const nav = document.querySelector(".nav");
            if (nav) {
                nav.classList.toggle("is-open");
            }
        });
    }

    document.querySelectorAll("[data-withdraw-form]").forEach((form) => {
        form.addEventListener("submit", (e) => {
            const ok = confirm("Withdraw from this unit? You can re-enroll later if slots are available.");
            if (!ok) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", () => {
            const btn = form.querySelector("button[type='submit']");
            if (!btn) return;
            if (!btn.dataset.originalText) {
                btn.dataset.originalText = btn.textContent;
            }
            btn.textContent = "Working...";
            btn.setAttribute("data-loading", "true");
            btn.disabled = true;
        });
    });

    
    const greetEl = document.querySelector("[data-greeting]");
    if (greetEl) {
        const hour = new Date().getHours();
        let greeting = "Welcome back";
        if (hour >= 5 && hour < 12) {
            greeting = "Good morning";
        } else if (hour >= 12 && hour < 17) {
            greeting = "Good afternoon";
        } else if (hour >= 17 && hour < 21) {
            greeting = "Good evening";
        } else {
            greeting = "Glad you're back";
        }
        greetEl.textContent = greeting;
    }
const focusToggle = document.querySelector("[data-focus-toggle]");
    if (focusToggle) {
        focusToggle.addEventListener("click", () => {
            const next = document.body.classList.contains("focus-mode") ? "off" : "on";
            applyLocalPreference("focus", next);
            const focusSwitch = document.querySelector("[data-local-switch='focus']");
            if (focusSwitch) {
                focusSwitch.checked = next === "on";
            }
        });
    }

    const accentRange = document.querySelector("[data-accent-range]");
    if (accentRange) {
        const presetHue = root.getAttribute("data-accent-hue");
        const storedHue = localStorage.getItem("accentHue");
        const hue = presetHue || storedHue;
        if (hue) {
            accentRange.value = hue;
            root.style.setProperty("--accent", `hsl(${hue} 90% 65%)`);
        }
        accentRange.addEventListener("input", (e) => {
            const newHue = e.target.value;
            root.style.setProperty("--accent", `hsl(${newHue} 90% 65%)`);
            localStorage.setItem("accentHue", newHue);
            savePreference({ accent_hue: newHue });
        });
    }

    const todayStrip = document.querySelector("[data-rotate] .today-text");
    if (todayStrip) {
        const messages = [
            "Review your available units and enroll before the deadline.",
            "Check your advisor details and keep your profile current.",
            "Export your enrolled units for offline planning.",
            "Aim to keep your unit load balanced this term."
        ];
        let idx = 0;
        setInterval(() => {
            idx = (idx + 1) % messages.length;
            todayStrip.textContent = messages[idx];
        }, 5000);
    }

    const notice = document.querySelector("[data-notice]");
    if (notice) {
        setTimeout(() => {
            notice.style.opacity = "0";
        }, 3500);
    }

    const noticeClose = document.querySelector("[data-notice-close]");
    if (noticeClose && notice) {
        noticeClose.addEventListener("click", () => {
            notice.style.display = "none";
        });
    }

    const showNotice = (text) => {
        if (notice) {
            notice.style.display = "flex";
            notice.style.opacity = "1";
            notice.querySelector("span").textContent = text;
            return;
        }
        const wrap = document.createElement("div");
        wrap.className = "notice-drawer";
        wrap.setAttribute("data-notice", "");
        wrap.innerHTML = `<span>${text}</span><button class="notice-close" type="button">Dismiss</button>`;
        const container = document.querySelector(".container");
        if (container) {
            container.insertBefore(wrap, container.children[2] || null);
            const close = wrap.querySelector(".notice-close");
            close.addEventListener("click", () => {
                wrap.style.display = "none";
            });
        }
    };

    const notes = document.querySelector(".notes");
    const saveNoteBtn = document.querySelector("[data-save-note]");
    if (notes && saveNoteBtn) {
        saveNoteBtn.addEventListener("click", () => {
            if (!csrf) return;
            saveNoteBtn.textContent = "Saving...";
            fetch("save_note.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({ body: notes.value, csrf_token: csrf })
            })
                .then(() => {
                    saveNoteBtn.textContent = "Save note";
                    showNotice("Notes saved.");
                })
                .catch(() => {
                    saveNoteBtn.textContent = "Save note";
                });
        });
    }

    let timer = null;
    let remaining = 25 * 60;
    let endTime = null;
    const storedRemaining = parseInt(localStorage.getItem("timerRemaining") || "", 10);
    const storedRunning = localStorage.getItem("timerRunning") === "1";
    const storedEnd = parseInt(localStorage.getItem("timerEnd") || "", 10);
    if (!Number.isNaN(storedRemaining)) {
        remaining = storedRemaining;
    }
    if (storedRunning && !Number.isNaN(storedEnd)) {
        endTime = storedEnd;
        remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
    }
    const display = document.querySelector("[data-timer-display]");
    const startBtn = document.querySelector("[data-timer-start]");
    const pauseBtn = document.querySelector("[data-timer-pause]");
    const resetBtn = document.querySelector("[data-timer-reset]");

    const renderTime = () => {
        if (!display) return;
        const m = String(Math.floor(remaining / 60)).padStart(2, "0");
        const s = String(remaining % 60).padStart(2, "0");
        display.textContent = `${m}:${s}`;
    };
    renderTime();

    const startInterval = () => {
        if (timer || !endTime) return;
        timer = setInterval(() => {
            remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
            localStorage.setItem("timerRemaining", String(remaining));
            renderTime();
            if (remaining === 0) {
                clearInterval(timer);
                timer = null;
                localStorage.setItem("timerRunning", "0");
                showNotice("Focus session complete.");
            }
        }, 1000);
    };

    if (storedRunning && endTime) {
        startInterval();
    }

    if (startBtn) {
        startBtn.addEventListener("click", () => {
            if (timer) return;
            endTime = Date.now() + remaining * 1000;
            localStorage.setItem("timerRunning", "1");
            localStorage.setItem("timerEnd", String(endTime));
            startInterval();
        });
    }
    if (pauseBtn) {
        pauseBtn.addEventListener("click", () => {
            clearInterval(timer);
            timer = null;
            localStorage.setItem("timerRunning", "0");
            localStorage.setItem("timerRemaining", String(remaining));
        });
    }
    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            remaining = 25 * 60;
            endTime = null;
            localStorage.setItem("timerRunning", "0");
            localStorage.setItem("timerRemaining", String(remaining));
            localStorage.removeItem("timerEnd");
            renderTime();
        });
    }

    const unitFilter = document.querySelector("[data-filter-units]");
    const unitsTable = document.querySelector("[data-units-table]");
    if (unitFilter && unitsTable) {
        unitFilter.addEventListener("input", () => {
            const q = unitFilter.value.toLowerCase();
            const rows = unitsTable.querySelectorAll("tbody tr");
            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? "" : "none";
            });
        });
    }

    const enrolledFilter = document.querySelector("[data-filter-enrolled]");
    const enrolledTable = document.querySelector("[data-enrolled-table]");
    if (enrolledFilter && enrolledTable) {
        enrolledFilter.addEventListener("input", () => {
            const q = enrolledFilter.value.toLowerCase();
            const rows = enrolledTable.querySelectorAll("tbody tr");
            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(q) ? "" : "none";
            });
        });
    }

    if (unitsTable) {
        unitsTable.querySelectorAll("thead th").forEach((th, idx) => {
            th.style.cursor = "pointer";
            th.addEventListener("click", () => {
                const rows = Array.from(unitsTable.querySelectorAll("tbody tr"));
                const sorted = rows.sort((a, b) => {
                    const aText = a.children[idx].textContent.trim();
                    const bText = b.children[idx].textContent.trim();
                    return aText.localeCompare(bText);
                });
                const tbody = unitsTable.querySelector("tbody");
                sorted.forEach((row) => tbody.appendChild(row));
            });
        });
    }

    const reveals = document.querySelectorAll(".card");
    if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        reveals.forEach((el) => observer.observe(el));
    }
})();






