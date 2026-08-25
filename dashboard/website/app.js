(function () {
  let currentLang = localStorage.getItem("najeh_user_lang") || "ar";

  function applyLanguage(lang) {
    currentLang = lang;
    localStorage.setItem("najeh_user_lang", lang);
    if (lang === "en") {
      document.body.classList.add("lang-en");
      document.documentElement.dir = "ltr";
      document.documentElement.lang = "en";
      document.getElementById("langBtnText").textContent = "العربية";
    } else {
      document.body.classList.remove("lang-en");
      document.documentElement.dir = "rtl";
      document.documentElement.lang = "ar";
      document.getElementById("langBtnText").textContent = "English";
    }
  }

  async function fetchLandingData() {
    try {
      const res = await fetch("/najeh/site-settings", {
        headers: {
          "Accept-Language": currentLang,
        },
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data.status === "success" && data.data) {
        renderHero(data.data.settings || {});
        renderStats(data.data.settings || {});
        renderBanners(data.data.banners || []);
        renderCourses(data.data.courses || []);
        renderBooks(data.data.books || []);
        renderFooter(data.data.settings || {});
      }
    } catch (err) { }
  }

  async function fetchAboutUsData() {
    try {
      const res = await fetch("/najeh/about-us", {
        headers: {
          "Accept-Language": currentLang,
        },
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data.status === "success" && data.data) {
        renderAboutUs(data.data);
      }
    } catch (err) { }
  }

  function renderHero(s) {
    document.getElementById("heroBadgeText").textContent = s.hero_badge_text || "";
    document.getElementById("heroTitle").textContent = s.hero_title || "";
    document.getElementById("heroSubtitle").textContent = s.hero_subtitle || "";

    const primaryBtn = document.getElementById("heroPrimaryBtn");
    if (primaryBtn) {
      primaryBtn.textContent = s.hero_primary_btn_text || (currentLang === "en" ? "Browse Courses" : "تصفح الكورسات");
      primaryBtn.href = s.hero_primary_btn_link || "#courses";
    }

    const secondaryBtn = document.getElementById("heroSecondaryBtn");
    if (secondaryBtn) {
      secondaryBtn.textContent = s.hero_secondary_btn_text || (currentLang === "en" ? "Subscription Plans" : "خطط الاشتراك");
      secondaryBtn.href = s.hero_secondary_btn_link || "#plans";
    }

    document.getElementById("heroSuccessRate").textContent = s.hero_success_rate || "94%";
    document.getElementById("heroLiveSessions").textContent = s.hero_live_sessions_count || "+1200";
    document.getElementById("heroRating").textContent = s.hero_rating || "4.9/5";
  }

  function renderStats(s) {
    document.getElementById("statCoursesVal").textContent = s.stats_courses_count || "+300";
    document.getElementById("statCoursesLabel").textContent = s.stats_courses_label || (currentLang === "en" ? "Educational Courses" : "كورس تعليمي");

    document.getElementById("statBooksVal").textContent = s.stats_books_count || "+120";
    document.getElementById("statBooksLabel").textContent = s.stats_books_label || (currentLang === "en" ? "Books & Notes" : "كتاب ومذكرة");

    document.getElementById("statTeachersVal").textContent = s.stats_teachers_count || "+85";
    document.getElementById("statTeachersLabel").textContent = s.stats_teachers_label || (currentLang === "en" ? "Expert Teachers" : "مدرّس متخصص");

    document.getElementById("statStudentsVal").textContent = s.stats_students_count || "+40k";
    document.getElementById("statStudentsLabel").textContent = s.stats_students_label || (currentLang === "en" ? "Enrolled Students" : "طالب مسجل");
  }

  function renderBanners(banners) {
    const wrap = document.getElementById("bannersContainer");
    if (!wrap) return;
    if (!banners.length) {
      wrap.style.display = "none";
      return;
    }
    wrap.style.display = "block";
    const slider = document.getElementById("bannersSlider");
    slider.innerHTML = banners
      .map(
        (b) => `
        <div style="min-width:100%;box-sizing:border-box;padding:10px">
          <a href="${b.link || "#"}" target="${b.link ? "_blank" : "_self"}">
            <img src="${b.image || b.image_ar || ""}" alt="${b.title || ""}" style="width:100%;height:320px;object-fit:cover;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,0.1)">
          </a>
        </div>`
      )
      .join("");
  }

  function renderCourses(courses) {
    const container = document.getElementById("coursesGrid");
    if (!container) return;
    if (!courses.length) {
      container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--slate-500)">${currentLang === "en" ? "No courses available" : "لا توجد كورسات متاحة حالياً"}</div>`;
      return;
    }
    container.innerHTML = courses
      .map(
        (c) => `
        <div class="course-card">
          <div class="card-img-wrap">
            <img class="card-img" src="${c.image || "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600"}" alt="${c.name}">
          </div>
          <div class="card-body">
            <div style="font-size:12px;font-weight:700;color:var(--amber-hover);margin-bottom:6px">👨‍🏫 ${c.teacher_name || (currentLang === "en" ? "Teacher" : "مدرس متخصص")}</div>
            <h3 class="card-title">${c.name}</h3>
            <p class="card-desc">${c.description || ""}</p>
            <div class="card-meta">
              <span class="price-tag">${c.price > 0 ? c.price + (currentLang === "en" ? " $" : "$") : (currentLang === "en" ? "Free" : "مجاني")}</span>
              <a href="/dashboard/student/" class="btn-primary" style="padding:8px 16px;font-size:13px">${currentLang === "en" ? "Subscribe" : "اشترك الآن"}</a>
            </div>
          </div>
        </div>`
      )
      .join("");
  }

  function renderBooks(books) {
    const container = document.getElementById("booksGrid");
    if (!container) return;
    if (!books.length) {
      container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--slate-500)">${currentLang === "en" ? "No books available" : "لا توجد كتب متاحة حالياً"}</div>`;
      return;
    }
    container.innerHTML = books
      .map(
        (b) => `
        <div class="book-card">
          <div class="card-img-wrap" style="height:160px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #101c48, #0a1333);color:#fbbf24;font-size:48px">
            📖
          </div>
          <div class="card-body">
            <h3 class="card-title">${b.title}</h3>
            <p class="card-desc">${b.description || ""}</p>
            <div class="card-meta">
              <span class="price-tag">${b.price > 0 ? b.price + (currentLang === "en" ? " $" : "$") : (currentLang === "en" ? "Free PDF" : "مجاني PDF")}</span>
              <a href="${b.file_path || "#"}" target="_blank" class="btn-outline" style="padding:8px 16px;font-size:13px;color:var(--slate-800);border-color:var(--slate-300)">${currentLang === "en" ? "Download" : "تصفح الكتاب"}</a>
            </div>
          </div>
        </div>`
      )
      .join("");
  }

  function renderAboutUs(data) {
    if (!data) return;
    const header = data.header || {};
    document.getElementById("aboutTitle").textContent = header.title || (currentLang === "en" ? "About Us" : "من نحن");
    document.getElementById("aboutDesc").textContent = header.description || "";

    const stats = data.stats || {};
    document.getElementById("aboutStatCourses").textContent = stats.courses || "150+";
    document.getElementById("aboutStatBooks").textContent = stats.books || "40+";
    document.getElementById("aboutStatStudents").textContent = stats.students || "20,000+";

    const mission = data.mission || {};
    document.getElementById("missionTitle").textContent = mission.title || (currentLang === "en" ? "Our Mission" : "رسالتنا");
    document.getElementById("missionText").textContent = mission.text || "";

    const offerings = data.offerings || [];
    const offWrap = document.getElementById("offeringsGrid");
    if (offWrap && offerings.length >= 3) {
      offWrap.innerHTML = offerings
        .map(
          (o, idx) => `
          <div class="about-card">
            <div class="icon-box" style="background:${idx === 0 ? "#eff6ff" : idx === 1 ? "#fffbeb" : "#ecfdf5"};color:${idx === 0 ? "#2563eb" : idx === 1 ? "#d97706" : "#059669"}">
              ${idx === 0 ? "📻" : idx === 1 ? "▶️" : "📖"}
            </div>
            <h3 style="font-size:20px;font-weight:800;margin-bottom:10px">${o.title}</h3>
            <p style="font-size:14px;color:var(--slate-500);line-height:1.6">${o.description}</p>
          </div>`
        )
        .join("");
    }

    const values = data.values || [];
    const valWrap = document.getElementById("valuesGrid");
    if (valWrap && values.length >= 4) {
      valWrap.innerHTML = values
        .map(
          (v, idx) => `
          <div class="about-card" style="text-align:${currentLang === "en" ? "left" : "right"};padding:24px;background:#f8fafc">
            <div class="icon-box" style="margin:0 0 16px;width:44px;height:44px;font-size:20px;background:${idx === 0 ? "#ffe4e6" : idx === 1 ? "#d1fae5" : idx === 2 ? "#e0f2fe" : "#fef3c7"}">
              ${idx === 0 ? "🎯" : idx === 1 ? "⏰" : idx === 2 ? "👥" : "🏆"}
            </div>
            <h4 style="font-size:17px;font-weight:800;margin-bottom:6px;color:var(--bg-dark)">${v.title}</h4>
            <p style="font-size:13px;color:var(--slate-500);line-height:1.5">${v.description}</p>
          </div>`
        )
        .join("");
    }

    const cta = data.cta || {};
    document.getElementById("aboutCtaTitle").textContent = cta.title || "";
    document.getElementById("aboutCtaSubtitle").textContent = cta.subtitle || "";
  }

  function renderFooter(s) {
    document.getElementById("footerDesc").textContent = s.footer_description || "";
    const fb = document.getElementById("footerFb");
    if (fb) fb.href = s.facebook_link || "https://facebook.com";
    const ig = document.getElementById("footerIg");
    if (ig) ig.href = s.instagram_link || "https://instagram.com";
    const yt = document.getElementById("footerYt");
    if (yt) yt.href = s.youtube_link || "https://youtube.com";

    // Inject WhatsApp number from DB into the floating button
    const waBtn = document.getElementById("whatsapp-float");
    if (waBtn) {
      const rawNum = (s.whatsapp_number || "201000000000").replace(/[^\d]/g, "");
      waBtn.href = "https://wa.me/" + rawNum;
      const tooltip = currentLang === "en" ? "Chat on WhatsApp" : "تواصل عبر واتساب";
      waBtn.setAttribute("aria-label", tooltip);
      waBtn.setAttribute("title", tooltip);
      // RTL: left side — LTR: right side
      waBtn.style.left = currentLang === "en" ? "auto" : "28px";
      waBtn.style.right = currentLang === "en" ? "28px" : "auto";
    }
  }

  document.getElementById("langSwitchBtn").addEventListener("click", () => {
    applyLanguage(currentLang === "ar" ? "en" : "ar");
    fetchLandingData();
    fetchAboutUsData();
  });

  applyLanguage(currentLang);
  fetchLandingData();
  fetchAboutUsData();
})();
