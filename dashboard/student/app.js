/* ==== Najeh Student Dashboard ==== */
const NS = "student";
let pendingEmail = "";

/* ---------- Response shape normalizer ---------- */
function extractList(res) {
  const d = res.data ?? res;
  let items = [];
  if (Array.isArray(d)) items = d;
  else if (Array.isArray(d.items)) items = d.items;
  else if (Array.isArray(d.data)) items = d.data;
  else if (Array.isArray(d.list)) items = d.list;

  const meta = res.pagination || res.meta || d.meta || d.pagination || {};
  const page = Number(meta.page || meta.current_page || d.page || 1);
  const totalPages = Number(meta.total_pages || meta.last_page || meta.pages || d.total_pages || 1);
  const total = meta.total ?? meta.total_items ?? meta.total_records ?? d.total ?? items.length;
  const statistics = res.statistics || d.statistics || null;
  return { items, page, totalPages, total, statistics };
}

function isLoggedIn() { return !!getTokens(NS).token; }

function showStep(step) {
  ["Login", "Register", "Verify", "Forgot", "Reset"].forEach((s) => {
    document.getElementById("step" + s).style.display = s === step ? "block" : "none";
  });
}

function showAuth() {
  document.getElementById("authView").style.display = "flex";
  document.getElementById("appShell").style.display = "none";
  showStep("Login");
}
function showApp() {
  document.getElementById("authView").style.display = "none";
  document.getElementById("appShell").style.display = "flex";
  navigate("home");
}

window.addEventListener("najeh:unauthorized", () => { toast("انتهت الجلسة، سجل الدخول مرة أخرى", "error"); showAuth(); });

/* ---- navigation between auth steps ---- */
document.getElementById("gotoRegister").onclick = (e) => { e.preventDefault(); renderImageWrap(); renderRegisterExtras(); showStep("Register"); };
document.getElementById("gotoLoginFromReg").onclick = (e) => { e.preventDefault(); showStep("Login"); };
document.getElementById("gotoForgot").onclick = (e) => { e.preventDefault(); showStep("Forgot"); };
document.getElementById("gotoLoginFromForgot").onclick = (e) => { e.preventDefault(); showStep("Login"); };

function renderImageWrap() {
  document.getElementById("regImageWrap").innerHTML = imageFieldHtml("regImage", "");
  wireImageField("regImage");
}

/* ---- Public (unauthenticated) lookups for the registration form ---- */
async function fetchPublicCities(page, search) {
  const res = await api.get(NS, "/cities" + qs({ page, limit: 20, search }), false);
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((r) => ({ id: r.id, label: translatedName(r) })), hasMore: p < totalPages };
}
async function fetchPublicSchools(page, search) {
  const res = await api.get(NS, "/schools" + qs({ page, limit: 20, search }), false);
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((r) => ({ id: r.id, label: translatedName(r) })), hasMore: p < totalPages };
}

function renderRegisterExtras() {
  document.getElementById("regCityWrap").innerHTML = asyncSelectHtml("regCity", { label: "المدينة" });
  wireAsyncSelect("regCity", fetchPublicCities);
  document.getElementById("regSchoolWrap").innerHTML = asyncSelectHtml("regSchool", { label: "المدرسة" });
  wireAsyncSelect("regSchool", fetchPublicSchools);
}

/* ---- LOGIN ---- */
document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("loginBtn");
  const errBox = document.getElementById("loginError");
  errBox.classList.remove("show");
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>`;
  try {
    const res = await api.post(NS, "/user/student/auth/login", {
      email: document.getElementById("loginEmail").value.trim(),
      password: document.getElementById("loginPassword").value,
    }, false);
    const d = res.data || res;
    const tokens = d.tokens || d;
    setTokens(NS, { token: tokens.access_token || tokens.token, refresh_token: tokens.refresh_token });
    Store.set("student_user", d.student || d.user || d);
    showApp();
  } catch (err) {
    if (err.data?.requires_email_verification) {
      pendingEmail = document.getElementById("loginEmail").value.trim();
      document.getElementById("verifyEmailLabel").textContent = pendingEmail;
      showStep("Verify");
    } else {
      errBox.textContent = err.message || "فشل تسجيل الدخول";
      errBox.classList.add("show");
    }
  } finally {
    btn.disabled = false; btn.textContent = "دخول";
  }
});

/* ---- REGISTER ---- */
document.getElementById("registerForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("registerBtn");
  const errBox = document.getElementById("registerError");
  errBox.classList.remove("show");
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>`;
  try {
    const payload = {
      name: document.getElementById("regName").value.trim(),
      email: document.getElementById("regEmail").value.trim(),
      password: document.getElementById("regPassword").value,
      phone: document.getElementById("regPhone").value.trim(),
      age: Number(document.getElementById("regAge").value) || undefined,
      city_id: readAsyncSelect("regCity"),
      school_id: readAsyncSelect("regSchool"),
      image: readImageField("regImage") || undefined,
    };
    await api.post(NS, "/user/student/auth/register", await buildRequestBody(payload), false);
    toast("تم التسجيل، برجاء تأكيد بريدك الإلكتروني");
    pendingEmail = payload.email;
    document.getElementById("verifyEmailLabel").textContent = pendingEmail;
    showStep("Verify");
  } catch (err) {
    errBox.textContent = err.message || "فشل التسجيل";
    errBox.classList.add("show");
  } finally {
    btn.disabled = false; btn.textContent = "تسجيل";
  }
});

/* ---- VERIFY EMAIL OTP ---- */
document.getElementById("verifyForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("verifyBtn");
  const errBox = document.getElementById("verifyError");
  errBox.classList.remove("show");
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>`;
  try {
    await api.post(NS, "/user/student/auth/verify-email", { email: pendingEmail, code: document.getElementById("verifyCode").value.trim() }, false);
    toast("تم تأكيد البريد الإلكتروني بنجاح، سجل الدخول");
    showStep("Login");
  } catch (err) {
    errBox.textContent = err.message || "كود غير صحيح";
    errBox.classList.add("show");
  } finally {
    btn.disabled = false; btn.textContent = "تأكيد";
  }
});
document.getElementById("resendOtpBtn").addEventListener("click", async () => {
  try {
    await api.post(NS, "/user/student/auth/resend-otp", { email: pendingEmail }, false);
    toast("تم إعادة إرسال الكود إلى بريدك");
  } catch (err) {
    toast(err.message || "فشل الإرسال", "error");
  }
});

/* ---- FORGOT PASSWORD ---- */
document.getElementById("forgotForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("forgotBtn");
  const errBox = document.getElementById("forgotError");
  errBox.classList.remove("show");
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>`;
  try {
    const email = document.getElementById("forgotEmail").value.trim();
    await api.post(NS, "/user/student/auth/forgot-password", { email }, false);
    pendingEmail = email;
    document.getElementById("resetEmailLabel").textContent = email;
    toast("تم إرسال كود إعادة التعيين إلى بريدك");
    showStep("Reset");
  } catch (err) {
    errBox.textContent = err.message || "فشل الإرسال";
    errBox.classList.add("show");
  } finally {
    btn.disabled = false; btn.textContent = "إرسال الكود";
  }
});

/* ---- RESET PASSWORD (verify + set) ---- */
document.getElementById("resetForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("resetBtn");
  const errBox = document.getElementById("resetError");
  errBox.classList.remove("show");
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>`;
  try {
    const code = document.getElementById("resetCode").value.trim();
    await api.post(NS, "/user/student/auth/verify-reset-code", { email: pendingEmail, code }, false);
    await api.post(NS, "/user/student/auth/reset-password", {
      email: pendingEmail,
      code,
      new_password: document.getElementById("resetNewPassword").value,
    }, false);
    toast("تم تغيير كلمة المرور بنجاح، سجل الدخول");
    showStep("Login");
  } catch (err) {
    errBox.textContent = err.message || "فشلت العملية";
    errBox.classList.add("show");
  } finally {
    btn.disabled = false; btn.textContent = "تعيين كلمة المرور";
  }
});

/* ---- Logout / shell ---- */
document.getElementById("logoutBtn").addEventListener("click", () => { clearTokens(NS); location.reload(); });

function toggleSidebar(open) {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  const willOpen = open ?? !sidebar.classList.contains("open");
  sidebar.classList.toggle("open", willOpen);
  overlay.classList.toggle("show", willOpen);
}
document.getElementById("menuToggle").addEventListener("click", () => toggleSidebar());
document.getElementById("sidebarOverlay").addEventListener("click", () => toggleSidebar(false));
document.getElementById("refreshBtn").addEventListener("click", () => (customPageRefresh ? customPageRefresh() : navigate(currentView)));

const studentTitles = {
  home: "الرئيسية",
  profile: "الملف الشخصي",
  redeem: "اشتراك بكود",
  "my-subscriptions": "اشتراكاتي",
  courses: "الكورسات",
  "my-books": "كتبي",
  certificates: "الشهادات",
  sessions: "حصص اليوم",
  homework: "الواجبات",
  exams: "الامتحانات",
  consultations: "الاستشارات",
  reports: "تقاريري الأكاديمية",
};
let currentView = "home";
let customPageRefresh = null;

function navigate(view) {
  currentView = view;
  customPageRefresh = null;
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.toggle("active", n.dataset.view === view));
  document.getElementById("pageTitle").textContent = studentTitles[view] || view;
  toggleSidebar(false);
  const renderers = {
    home: renderHomeView,
    profile: renderProfileView,
    redeem: renderRedeemView,
    "my-subscriptions": renderMySubscriptionsView,
    courses: renderCoursesView,
    "my-books": renderMyBooksView,
    certificates: renderCertificatesView,
    sessions: renderSessionsView,
    homework: renderHomeworkView,
    exams: renderExamsView,
    consultations: renderConsultationsView,
    reports: renderReportsView,
  };
  const fn = renderers[view];
  const root = document.getElementById("viewRoot");
  root.innerHTML = "";
  if (fn) fn(root);
}
document.querySelectorAll(".nav-item").forEach((item) => (item.onclick = () => navigate(item.dataset.view)));

/* ================= Home ================= */
async function renderHomeView(root) {
  root.innerHTML = `<div id="homeLoading" style="text-align:center;padding:40px"><span class="spinner spinner-dark"></span></div>`;
  try {
    const res = await api.get(NS, "/student/home");
    const d = res.data || res;
    root.innerHTML = `
      <div class="stats-bar">
        <div class="stat-chip stat-chip-primary"><span class="stat-chip-icon">💳</span><span class="stat-chip-text"><span class="stat-chip-value">${d.subscriptions_count ?? 0}</span><span class="stat-chip-label">اشتراكاتي</span></span></div>
        <div class="stat-chip stat-chip-success"><span class="stat-chip-icon">🏆</span><span class="stat-chip-text"><span class="stat-chip-value">${d.certificates_count ?? 0}</span><span class="stat-chip-label">الشهادات</span></span></div>
        <div class="stat-chip stat-chip-warning"><span class="stat-chip-icon">📖</span><span class="stat-chip-text"><span class="stat-chip-value">${d.books_count ?? 0}</span><span class="stat-chip-label">كتبي</span></span></div>
        <div class="stat-chip stat-chip-muted"><span class="stat-chip-icon">📊</span><span class="stat-chip-text"><span class="stat-chip-value">${Math.round(d.overall_progress ?? 0)}%</span><span class="stat-chip-label">التقدم العام</span></span></div>
      </div>
      ${(d.today_sessions || []).length
        ? `<div class="card">
              <div class="card-header"><h3>📅 حصص اليوم</h3></div>
              <div class="table-scroll"><table>
                <thead><tr><th>الكود</th><th>الوقت</th><th>المكان</th><th></th></tr></thead>
                <tbody>${d.today_sessions
          .map(
            (s) => `<tr>
                      <td>${escapeHtml(s.code || "-")}</td>
                      <td>${s.start_time}-${s.end_time} ${s.is_available_now ? `<span class="badge badge-success">شغالة دلوقتي</span>` : ""}</td>
                      <td>${s.is_online ? "🔗 أونلاين" : escapeHtml(s.room || "-")}</td>
                      <td>${s.is_online && s.meeting_link ? `<a href="${escapeHtml(s.meeting_link)}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">دخول الحصة</a>` : ""}</td>
                    </tr>`
          )
          .join("")}</tbody>
              </table></div>
            </div>`
        : ""
      }
      <div class="card">
        <div class="card-header"><h3>📚 كورساتي المشترك بها</h3></div>
        <div class="table-scroll"><table>
          <thead><tr><th>الكورس</th><th>نسبة التقدم</th></tr></thead>
          <tbody>${(d.subscribed_courses || []).length
        ? d.subscribed_courses
          .map(
            (c) => `<tr>
                      <td>${escapeHtml(c.name || translatedName(c))}</td>
                      <td><div class="progress-bar"><div class="progress-bar-fill" style="width:${c.progress_percentage || 0}%"></div></div> ${Math.round(c.progress_percentage || 0)}%</td>
                    </tr>`
          )
          .join("")
        : `<tr><td colspan="2"><div class="empty-state">لا توجد اشتراكات بعد — اشترك بكود من القائمة الجانبية</div></td></tr>`
      }</tbody>
        </table></div>
      </div>`;
  } catch (err) {
    root.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Profile ================= */
async function renderProfileView(root) {
  root.innerHTML = `<div style="text-align:center;padding:40px"><span class="spinner spinner-dark"></span></div>`;
  try {
    const res = await api.get(NS, "/user/student/auth/profile");
    const s = res.data || res;
    document.getElementById("sideUserName").textContent = s.name || "الطالب";
    document.getElementById("sideUserRole").textContent = s.email || "";
    if (s.image) document.getElementById("sideUserImg").src = s.image;
    root.innerHTML = `
      <div class="card">
        <div class="card-body" style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
          <img src="${escapeHtml(s.image || "https://ui-avatars.com/api/?name=" + encodeURIComponent(s.name || "Student"))}" style="width:90px;height:90px;border-radius:50%;object-fit:cover">
          <div>
            <h2 style="margin:0 0 6px">${escapeHtml(s.name)}</h2>
            <div class="text-muted">${escapeHtml(s.email)} ${verifiedBadge(s.is_email_verified)}</div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="grid-2">
            <div><b>الهاتف:</b> ${escapeHtml(s.phone || "-")}</div>
            <div><b>السن:</b> ${s.age ?? "-"}</div>
            <div><b>المدينة:</b> ${escapeHtml(translatedName(s.city) || "-")}</div>
            <div><b>المدرسة:</b> ${escapeHtml(translatedName(s.school) || "-")}</div>
          </div>
        </div>
      </div>`;
  } catch (err) {
    root.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Redeem subscription code ================= */
function renderRedeemView(root) {
  root.innerHTML = `
    <div class="card" style="max-width:480px">
      <div class="card-header"><h3>🎟️ اشتراك بكود</h3></div>
      <div class="card-body">
        <p class="text-muted">أدخل كود الاشتراك اللي معاك عشان تفتح المحتوى المرتبط بيه (كورس / مادة / معلم).</p>
        <div class="field"><label>كود الاشتراك</label><input type="text" id="redeemCode" placeholder="مثال: NAJEH-2026-XXXX"></div>
        <button class="btn btn-primary btn-block" id="redeemBtn">تفعيل الكود</button>
        <div id="redeemResult" style="margin-top:16px"></div>
      </div>
    </div>`;
  document.getElementById("redeemBtn").onclick = async () => {
    const btn = document.getElementById("redeemBtn");
    const code = document.getElementById("redeemCode").value.trim();
    if (!code) return toast("اكتب الكود الأول", "error");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      const res = await api.post(NS, "/student/subscriptions/redeem-code", { code });
      const sub = res.data || res;
      toast("تم تفعيل الاشتراك بنجاح!");
      document.getElementById("redeemResult").innerHTML = `
        <div class="card">
          <div class="card-body">
            <div style="font-weight:700;margin-bottom:6px">✅ ${escapeHtml(sub.subscription_type || "")}</div>
            <div class="text-muted" style="font-size:13px">ساري حتى: ${fmtDate(sub.end_date)}</div>
            ${sub.courses?.length ? `<div style="margin-top:8px"><b>الكورسات:</b> ${sub.courses.map((c) => escapeHtml(c.name || "")).join("، ")}</div>` : ""}
            ${sub.subjects?.length ? `<div style="margin-top:8px"><b>المواد:</b> ${sub.subjects.map((s) => escapeHtml(s.name || "")).join("، ")}</div>` : ""}
          </div>
        </div>`;
      document.getElementById("redeemCode").value = "";
    } catch (err) {
      toast(err.message || "فشل تفعيل الكود", "error");
    } finally {
      btn.disabled = false;
      btn.textContent = "تفعيل الكود";
    }
  };
}

/* ================= My Subscriptions ================= */
async function renderMySubscriptionsView(root) {
  root.innerHTML = `<div id="msList"><div style="text-align:center;padding:40px"><span class="spinner spinner-dark"></span></div></div>`;
  const box = document.getElementById("msList");
  try {
    const res = await api.get(NS, "/student/subscriptions/my-subscriptions");
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      box.innerHTML = `<div class="card"><div class="card-body"><div class="empty-state">لا توجد اشتراكات — اشترك بكود من القائمة الجانبية</div></div></div>`;
      return;
    }
    box.innerHTML = list
      .map(
        (s) => `<div class="card">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
              <div style="font-weight:700">${escapeHtml(s.subscription_type || "-")} — ${escapeHtml(s.code_string || "")}</div>
              ${s.is_currently_valid ? `<span class="badge badge-success">سارٍ</span>` : `<span class="badge badge-danger">منتهي</span>`}
            </div>
            <div class="text-muted" style="font-size:13px;margin-top:6px">من ${fmtDate(s.start_date)} إلى ${fmtDate(s.end_date)}</div>
            ${s.courses?.length ? `<div style="margin-top:8px"><b>الكورسات:</b> ${s.courses.map((c) => escapeHtml(c.name || "")).join("، ")}</div>` : ""}
            ${s.subjects?.length ? `<div style="margin-top:8px"><b>المواد:</b> ${s.subjects.map((x) => escapeHtml(x.name || "")).join("، ")}</div>` : ""}
            ${s.teachers?.length ? `<div style="margin-top:8px"><b>المعلمون:</b> ${s.teachers.map((x) => escapeHtml(x.name || "")).join("، ")}</div>` : ""}
          </div>
        </div>`
      )
      .join("");
  } catch (err) {
    box.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Courses ================= */
const coursesState = { page: 1, perPage: 20 };
async function renderCoursesView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>الكورسات</h3>
        <input type="search" id="courseSearch" placeholder="بحث..." style="width:220px">
      </div>
      <div id="coursesGrid" class="cards-grid"><div style="text-align:center;padding:40px;grid-column:1/-1"><span class="spinner spinner-dark"></span></div></div>
      <div id="coursesPg"></div>
    </div>`;
  let debounce;
  document.getElementById("courseSearch").addEventListener("input", (e) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => { coursesState.search = e.target.value.trim(); coursesState.page = 1; loadCoursesGrid(); }, 350);
  });
  await loadCoursesGrid();
}
async function loadCoursesGrid() {
  const grid = document.getElementById("coursesGrid");
  if (!grid) return;
  try {
    const res = await api.get(NS, "/student/courses" + qs({ page: coursesState.page, limit: coursesState.perPage, search: coursesState.search }));
    const { items, page, totalPages, total } = extractList(res);
    if (!items.length) {
      grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">لا توجد كورسات</div>`;
    } else {
      grid.innerHTML = items
        .map(
          (c) => `<div class="content-card">
            <img src="${escapeHtml(c.image || "")}" onerror="this.style.display='none'">
            <div class="content-card-body">
              <div style="font-weight:700">${escapeHtml(c.name || translatedName(c))}</div>
              <div class="text-muted" style="font-size:13px;margin:6px 0">${c.price ? c.price + " $" : "مجاني"}</div>
              ${c.is_subscribed ? `<div class="progress-bar"><div class="progress-bar-fill" style="width:${c.progress_percentage || 0}%"></div></div>` : ""}
              <button class="btn btn-outline btn-sm btn-block" data-act="view" data-id="${c.id}" style="margin-top:8px">${c.is_subscribed ? "متابعة المذاكرة" : "عرض التفاصيل"}</button>
            </div>
          </div>`
        )
        .join("");
      grid.querySelectorAll("[data-act=view]").forEach((b) => (b.onclick = () => openCourseDetailsModal(Number(b.dataset.id))));
    }
    const pgEl = document.getElementById("coursesPg");
    pgEl.innerHTML = paginationHtml(page, totalPages, total, coursesState.perPage);
    wirePagination(pgEl, coursesState, loadCoursesGrid);
  } catch (err) {
    grid.innerHTML = `<div class="error-box show" style="grid-column:1/-1">${escapeHtml(err.message)}</div>`;
  }
}

async function openCourseDetailsModal(id) {
  Modal.open({ title: "تفاصيل الكورس", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: "" });
  try {
    const res = await api.get(NS, "/student/courses/show" + qs({ id }));
    const c = res.data || res;
    document.getElementById("modalBody").innerHTML = `
      <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px">
        <img src="${escapeHtml(c.image || "")}" style="width:160px;border-radius:10px" onerror="this.style.display='none'">
        <div style="flex:1;min-width:200px">
          <h2 style="margin:0 0 8px">${escapeHtml(c.name)}</h2>
          <div class="text-muted">${escapeHtml(c.description || "")}</div>
          <div style="margin-top:8px">⭐ ${c.rating ?? "-"} (${c.reviews_count ?? 0} تقييم) — 👥 ${c.students_count ?? 0} طالب</div>
          <div style="margin-top:6px;font-weight:700">${c.price ? c.price + " $" : "مجاني"}</div>
          ${c.is_subscribed ? `<div style="margin-top:8px"><div class="progress-bar"><div class="progress-bar-fill" style="width:${c.progress_percentage || 0}%"></div></div> ${Math.round(c.progress_percentage || 0)}% مكتمل</div>` : `<div class="text-muted" style="margin-top:8px">اشترك بكود من القائمة الجانبية عشان تفتح كل محتوى الكورس ده</div>`}
        </div>
      </div>
      ${(c.overview?.what_you_will_learn || []).length ? `<div class="card"><div class="card-body"><b>هتتعلم إيه:</b><ul>${c.overview.what_you_will_learn.map((x) => `<li>${escapeHtml(x)}</li>`).join("")}</ul></div></div>` : ""}
      <div class="card"><div class="card-body">
        <b>محتوى الكورس</b>
        ${(c.curriculum || [])
        .map(
          (unit) => `<div style="margin-top:12px">
              <div style="font-weight:700">${escapeHtml(unit.name)}</div>
              ${(unit.lessons || [])
              .map(
                (l) => `<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)">
                    <span>${l.is_locked ? "🔒" : l.is_completed ? "✅" : "▶️"} ${escapeHtml(l.name || l.title)}</span>
                    <span class="text-muted" style="font-size:12px">${l.duration || ""}</span>
                  </div>`
              )
              .join("")}
            </div>`
        )
        .join("") || `<div class="text-muted">لا يوجد محتوى بعد</div>`}
      </div></div>
      <div class="card"><div class="card-body">
        <b>قيّم الكورس</b>
        <div class="field"><select id="rateStars"><option value="5">5 - ممتاز</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option></select></div>
        <div class="field"><textarea id="rateComment" rows="2" placeholder="تعليقك (اختياري)"></textarea></div>
        <button class="btn btn-primary btn-sm" id="rateBtn">إرسال التقييم</button>
      </div></div>`;
    document.getElementById("rateBtn").onclick = async () => {
      try {
        await api.post(NS, "/student/courses/rate", { course_id: id, rating: Number(document.getElementById("rateStars").value), comment: document.getElementById("rateComment").value.trim() });
        toast("شكرًا لتقييمك!");
        Modal.close();
      } catch (err) {
        toast(err.message || "فشلت العملية", "error");
      }
    };
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= My Books ================= */
const booksState = { page: 1, perPage: 20 };
async function renderMyBooksView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header"><h3>كتبي</h3></div>
      <div class="table-scroll"><table>
        <thead><tr><th>العنوان</th><th>النوع</th><th>السعر</th><th>إجراءات</th></tr></thead>
        <tbody id="booksTbody"><tr class="loading-row"><td colspan="4"><span class="spinner spinner-dark"></span></td></tr></tbody>
      </table></div>
      <div id="booksPg"></div>
    </div>`;
  await loadMyBooks();
}
async function loadMyBooks() {
  const tbody = document.getElementById("booksTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/student/my-subscribed-books" + qs({ page: booksState.page, limit: booksState.perPage }));
    const { items, page, totalPages, total } = extractList(res);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state">لا توجد كتب متاحة بعد</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map(
          (b) => `<tr>
            <td>${escapeHtml(b.title || "-")}</td>
            <td>${escapeHtml((b.file_path || "").split(".").pop().slice(0, 5).toUpperCase() || "-")}</td>
            <td>${b.price > 0 ? b.price + " $" : "مجاني"}</td>
            <td class="row-actions"><a href="${escapeHtml(b.file_path || "")}" target="_blank" rel="noopener" class="btn btn-outline btn-sm" data-act="open" data-id="${b.id}">📖 فتح الكتاب</a></td>
          </tr>`
        )
        .join("");
      tbody.querySelectorAll("[data-act=open]").forEach((a) => (a.onclick = () => api.post(NS, "/student/books/track-open", { book_id: Number(a.dataset.id) }).catch(() => { })));
    }
    const pgEl = document.getElementById("booksPg");
    pgEl.innerHTML = paginationHtml(page, totalPages, total, booksState.perPage);
    wirePagination(pgEl, booksState, loadMyBooks);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

/* ================= Certificates ================= */
async function renderCertificatesView(root) {
  root.innerHTML = `<div id="certBox"><div style="text-align:center;padding:40px"><span class="spinner spinner-dark"></span></div></div>`;
  const box = document.getElementById("certBox");
  try {
    const res = await api.get(NS, "/student/my-certificates");
    const d = res.data || res;
    const issued = d.issued_certificates || [];
    const available = d.available_certificates || [];
    box.innerHTML = `
      <div class="card">
        <div class="card-header"><h3>🏆 شهاداتي</h3></div>
        <div class="table-scroll"><table>
          <thead><tr><th>الشهادة</th><th>تاريخ الإصدار</th><th>التقدير</th><th></th></tr></thead>
          <tbody>${issued.length
        ? issued
          .map(
            (c) => `<tr>
                      <td>${escapeHtml(c.name || "-")}</td>
                      <td>${fmtDate(c.issue_date)}</td>
                      <td>${escapeHtml(c.grade_obtained || "-")}</td>
                      <td>${c.pdf_url ? `<a href="${escapeHtml(c.pdf_url)}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">تحميل</a>` : "-"}</td>
                    </tr>`
          )
          .join("")
        : `<tr><td colspan="4"><div class="empty-state">لسه معندكش شهادات صادرة</div></td></tr>`
      }</tbody>
        </table></div>
      </div>
      <div class="card">
        <div class="card-header"><h3>الشهادات المتاحة</h3></div>
        <div class="table-scroll"><table>
          <thead><tr><th>الشهادة</th><th>النوع</th></tr></thead>
          <tbody>${available.length
        ? available.map((c) => `<tr><td>${escapeHtml(c.name || "-")}</td><td>${escapeHtml(c.certificate_type || "-")}</td></tr>`).join("")
        : `<tr><td colspan="2"><div class="empty-state">لا توجد شهادات متاحة حاليًا</div></td></tr>`
      }</tbody>
        </table></div>
      </div>`;
  } catch (err) {
    box.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Sessions (today) ================= */
const SESSION_DAY_OPTIONS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const SESSION_DAY_LABELS_AR = { Sunday: "الأحد", Monday: "الإثنين", Tuesday: "الثلاثاء", Wednesday: "الأربعاء", Thursday: "الخميس", Friday: "الجمعة", Saturday: "السبت" };

async function renderSessionsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>📅 جدول حصصي</h3>
        <div class="toolbar">
          <select id="sesDaySelect" style="width:auto">${SESSION_DAY_OPTIONS.map((d) => `<option value="${d}">${SESSION_DAY_LABELS_AR[d]}</option>`).join("")}</select>
          <button class="btn btn-outline btn-sm" id="myReqBtn">📋 طلبات التغيير بتاعتي</button>
        </div>
      </div>
      <div class="table-scroll"><table>
        <thead><tr><th>الكود</th><th>اليوم</th><th>الوقت</th><th>المكان</th><th></th><th></th></tr></thead>
        <tbody id="stSesTbody"><tr class="loading-row"><td colspan="6"><span class="spinner spinner-dark"></span></td></tr></tbody>
      </table></div>
    </div>`;
  const todayName = new Date().toLocaleDateString("en-US", { weekday: "long" });
  document.getElementById("sesDaySelect").value = SESSION_DAY_OPTIONS.includes(todayName) ? todayName : "Sunday";
  document.getElementById("sesDaySelect").onchange = () => loadStudentSessions();
  document.getElementById("myReqBtn").onclick = () => openMyStudentRescheduleRequestsModal();
  await loadStudentSessions();
}

async function loadStudentSessions() {
  const tbody = document.getElementById("stSesTbody");
  if (!tbody) return;
  tbody.innerHTML = `<tr class="loading-row"><td colspan="6"><span class="spinner spinner-dark"></span></td></tr>`;
  try {
    const day = document.getElementById("sesDaySelect").value;
    const res = await api.get(NS, "/student/sessions/today" + qs({ day }));
    const d = res.data || res;
    const list = d.sessions || (Array.isArray(d) ? d : []);
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">لا توجد حصص في هذا اليوم</div></td></tr>`;
      return;
    }
    tbody.innerHTML = list
      .map(
        (s) => `<tr>
          <td>${escapeHtml(s.code || "-")}</td>
          <td>${SESSION_DAY_LABELS_AR[s.day_of_week] || escapeHtml(s.day_of_week)}</td>
          <td>${s.start_time}-${s.end_time} ${s.is_available_now ? `<span class="badge badge-success">شغالة دلوقتي</span>` : ""}</td>
          <td>${s.is_online ? "🔗 أونلاين" : escapeHtml(s.room || "-")}</td>
          <td>${s.is_online && s.meeting_link ? `<a href="${escapeHtml(s.meeting_link)}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">دخول الحصة</a>` : ""}</td>
          <td><button class="btn btn-outline btn-sm" data-act="reschedule" data-id="${s.id}">📅 طلب تغيير</button></td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=reschedule]").forEach((b) => (b.onclick = () => openStudentRescheduleRequestForm(list.find((i) => String(i.id) === b.dataset.id))));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

function openStudentRescheduleRequestForm(session) {
  Modal.open({
    title: "طلب تغيير ميعاد الحصة",
    bodyHtml: `
      <div class="field">
        <label>الحصة الحالية</label>
        <div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${escapeHtml(session.code || "-")} — ${SESSION_DAY_LABELS_AR[session.day_of_week] || session.day_of_week} (${session.start_time}-${session.end_time})</div>
      </div>
      <div class="field"><label>اليوم المقترح *</label><select id="srq_day">${SESSION_DAY_OPTIONS.map((d) => `<option value="${d}">${SESSION_DAY_LABELS_AR[d]}</option>`).join("")}</select></div>
      <div class="grid-2">
        <div class="field"><label>من الساعة</label><input type="time" id="srq_start" value="${session.start_time?.slice(0, 5) || ""}"></div>
        <div class="field"><label>إلى الساعة</label><input type="time" id="srq_end" value="${session.end_time?.slice(0, 5) || ""}"></div>
      </div>
      <div class="field"><label>سبب الطلب *</label><textarea id="srq_reason" rows="2" required></textarea></div>`,
    footerHtml: `<button class="btn btn-outline" id="srqCancel">إلغاء</button><button class="btn btn-primary" id="srqSend">إرسال للمعلم</button>`,
  });
  document.getElementById("srqCancel").onclick = () => Modal.close();
  document.getElementById("srqSend").onclick = async () => {
    const btn = document.getElementById("srqSend");
    const reason = document.getElementById("srq_reason").value.trim();
    if (!reason) return toast("اكتب سبب الطلب", "error");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/student/sessions/reschedule-request", {
        session_id: session.id,
        new_day: document.getElementById("srq_day").value,
        new_start_time: document.getElementById("srq_start").value,
        new_end_time: document.getElementById("srq_end").value,
        reason,
      });
      toast("تم إرسال الطلب للمعلم بنجاح");
      Modal.close();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "إرسال للمعلم";
    }
  };
}

const STUDENT_REQ_STATUS_BADGES = { pending: `<span class="badge badge-warning">معلّقة</span>`, approved: `<span class="badge badge-success">تمت الموافقة</span>`, rejected: `<span class="badge badge-danger">مرفوضة</span>` };
async function openMyStudentRescheduleRequestsModal() {
  Modal.open({ title: "طلبات تغيير المواعيد بتاعتي", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" id="mrqClose">إغلاق</button>` });
  document.getElementById("mrqClose").onclick = () => Modal.close();
  try {
    const res = await api.get(NS, "/student/sessions/reschedule-requests");
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      document.getElementById("modalBody").innerHTML = `<div class="empty-state">لا توجد طلبات</div>`;
      return;
    }
    document.getElementById("modalBody").innerHTML = `
      <div class="table-scroll"><table>
        <thead><tr><th>الحصة</th><th>اليوم المقترح</th><th>السبب</th><th>الحالة</th></tr></thead>
        <tbody>${list
        .map(
          (r) => `<tr>
              <td>${escapeHtml(r.session?.code || "-")}</td>
              <td>${SESSION_DAY_LABELS_AR[r.new_day] || r.new_day} (${r.new_start_time || ""}-${r.new_end_time || ""})</td>
              <td style="max-width:200px;white-space:normal">${escapeHtml(r.reason)}</td>
              <td>${STUDENT_REQ_STATUS_BADGES[r.status] || escapeHtml(r.status)}</td>
            </tr>`
        )
        .join("")}</tbody>
      </table></div>`;
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Homework ================= */
async function renderHomeworkView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header"><h3>📋 الواجبات</h3></div>
      <div class="table-scroll"><table>
        <thead><tr><th>العنوان</th><th>آخر ميعاد للتسليم</th><th>حالة التسليم</th><th></th></tr></thead>
        <tbody id="stHwTbody"><tr class="loading-row"><td colspan="4"><span class="spinner spinner-dark"></span></td></tr></tbody>
      </table></div>
      <div id="stHwPg"></div>
    </div>`;
  await loadStudentHomework();
}
const hwState = { page: 1, perPage: 20 };
const HW_STATUS_BADGES = {
  submitted: `<span class="badge badge-warning">بانتظار التقييم</span>`,
  reviewed: `<span class="badge badge-success">تم التقييم</span>`,
};
async function loadStudentHomework() {
  const tbody = document.getElementById("stHwTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/student/homework" + qs({ page: hwState.page, limit: hwState.perPage }));
    const { items, page, totalPages, total } = extractList(res);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state">لا توجد واجبات حاليًا</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map(
          (h) => `<tr>
            <td>${escapeHtml(h.title)}</td>
            <td>${h.due_date || "-"}</td>
            <td>${h.my_submission ? HW_STATUS_BADGES[h.my_submission.status] || escapeHtml(h.my_submission.status) : `<span class="badge badge-muted">لم يُسلّم بعد</span>`}</td>
            <td class="row-actions">
              ${h.file ? `<a href="${escapeHtml(h.file)}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">📄 ملف الواجب</a>` : ""}
              <button class="btn btn-primary btn-sm" data-act="submit" data-id="${h.id}">${h.my_submission ? "إعادة التسليم" : "تسليم الحل"}</button>
              ${h.my_submission?.feedback_text ? `<button class="btn btn-outline btn-sm" data-act="fb" data-fb="${escapeHtml(h.my_submission.feedback_text)}" data-rating="${h.my_submission.feedback_rating || ""}">عرض الفيدباك</button>` : ""}
            </td>
          </tr>`
        )
        .join("");
      tbody.querySelectorAll("[data-act=submit]").forEach((b) => (b.onclick = () => openHomeworkSubmitModal(Number(b.dataset.id))));
      tbody.querySelectorAll("[data-act=fb]").forEach(
        (b) =>
        (b.onclick = () =>
          Modal.open({ title: "فيدباك المعلم", bodyHtml: `<p>${escapeHtml(b.dataset.fb)}</p>${b.dataset.rating ? `<p>التقييم: ${b.dataset.rating}/5</p>` : ""}`, footerHtml: `<button class="btn btn-outline" onclick="Modal.close()">إغلاق</button>` }))
      );
    }
    const pgEl = document.getElementById("stHwPg");
    pgEl.innerHTML = paginationHtml(page, totalPages, total, hwState.perPage);
    wirePagination(pgEl, hwState, loadStudentHomework);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}
function openHomeworkSubmitModal(homeworkId) {
  Modal.open({
    title: "تسليم حل الواجب",
    bodyHtml: `${imageFieldHtml("hwFile", "", { kind: "file", label: "ملف الحل (ارفعه من جهازك أو حط لينك)" })}`,
    footerHtml: `<button class="btn btn-outline" id="hwCancel">إلغاء</button><button class="btn btn-primary" id="hwSubmit">تسليم</button>`,
  });
  wireImageField("hwFile");
  document.getElementById("hwCancel").onclick = () => Modal.close();
  document.getElementById("hwSubmit").onclick = async () => {
    const btn = document.getElementById("hwSubmit");
    const file = readImageField("hwFile");
    if (!file) return toast("ارفع ملف الحل الأول", "error");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/student/homework/submit", await buildRequestBody({ homework_id: homeworkId, file }));
      toast("تم تسليم الحل بنجاح");
      Modal.close();
      loadStudentHomework();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "تسليم";
    }
  };
}

/* ================= Exams / Attempts ================= */
async function renderExamsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header"><h3>📝 الامتحانات</h3></div>
      <div class="table-scroll"><table>
        <thead><tr><th>الامتحان</th><th>عدد الأسئلة</th><th>الدرجة الكلية</th><th>الحالة</th><th></th></tr></thead>
        <tbody id="stExTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
      </table></div>
    </div>`;
  await loadStudentExams();
}
const EXAM_STATUS_BADGES = {
  in_progress: `<span class="badge badge-muted">جاري الحل</span>`,
  submitted: `<span class="badge badge-warning">بانتظار التصحيح</span>`,
  graded: `<span class="badge badge-success">تم التصحيح</span>`,
};
async function loadStudentExams() {
  const tbody = document.getElementById("stExTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/student/exams");
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد امتحانات متاحة حاليًا</div></td></tr>`;
      return;
    }
    tbody.innerHTML = list
      .map(
        (e) => `<tr>
          <td>${escapeHtml(e.title || "-")}</td>
          <td>${e.questions_count ?? "-"}</td>
          <td>${e.total_marks ?? "-"}</td>
          <td>${e.my_attempt ? EXAM_STATUS_BADGES[e.my_attempt.status] || escapeHtml(e.my_attempt.status) : `<span class="badge badge-muted">لم يُحل بعد</span>`}</td>
          <td class="row-actions">
            ${!e.my_attempt ? `<button class="btn btn-primary btn-sm" data-act="start" data-id="${e.id}">ابدأ الامتحان</button>` : ""}
            ${e.my_attempt && e.my_attempt.status !== "in_progress" ? `<button class="btn btn-outline btn-sm" data-act="results" data-id="${e.my_attempt.id}">عرض النتيجة</button>` : ""}
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=start]").forEach((b) => (b.onclick = () => startExamAttempt(Number(b.dataset.id))));
    tbody.querySelectorAll("[data-act=results]").forEach((b) => (b.onclick = () => showExamResultModal(Number(b.dataset.id))));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

async function startExamAttempt(examId) {
  try {
    const res = await api.post(NS, "/student/exams/start", { exam_id: examId });
    const d = res.data || res;
    openExamTakingModal(d);
  } catch (err) {
    toast(err.message || "تعذر بدء الامتحان", "error");
  }
}

function openExamTakingModal(attemptData) {
  const questions = attemptData.questions || [];
  Modal.open({
    title: `حل الامتحان: ${escapeHtml(attemptData.exam?.title || "")}`,
    size: "modal-lg",
    bodyHtml: questions
      .map(
        (q, idx) => `<div class="card" style="margin-bottom:10px">
          <div class="card-body">
            <div style="font-weight:700;margin-bottom:8px">${idx + 1}. ${escapeHtml(q.question_text)}</div>
            ${q.question_type === "mcq"
            ? (q.options || [])
              .map((o) => `<label style="display:flex;align-items:center;gap:8px;padding:4px 0"><input type="radio" name="eq_${q.id}" value="${o.id}" style="width:16px;height:16px;flex:none"> ${escapeHtml(o.option_text)}</label>`)
              .join("")
            : q.question_type === "true_false"
              ? `<label style="display:flex;align-items:center;gap:8px;padding:4px 0"><input type="radio" name="eq_${q.id}" value="1" style="width:16px;height:16px;flex:none"> صح</label>
                   <label style="display:flex;align-items:center;gap:8px;padding:4px 0"><input type="radio" name="eq_${q.id}" value="0" style="width:16px;height:16px;flex:none"> خطأ</label>`
              : `<textarea id="eq_written_${q.id}" rows="3" placeholder="اكتب إجابتك هنا..."></textarea>`
          }
          </div>
        </div>`
      )
      .join(""),
    footerHtml: `<button class="btn btn-primary btn-block" id="examSubmitBtn">تسليم الامتحان</button>`,
  });
  document.getElementById("examSubmitBtn").onclick = async () => {
    const btn = document.getElementById("examSubmitBtn");
    const answers = questions.map((q) => {
      if (q.question_type === "mcq") {
        const checked = document.querySelector(`input[name="eq_${q.id}"]:checked`);
        return { question_id: q.id, mcq_option_id: checked ? Number(checked.value) : undefined };
      }
      if (q.question_type === "true_false") {
        const checked = document.querySelector(`input[name="eq_${q.id}"]:checked`);
        return { question_id: q.id, true_false_answer: checked ? Number(checked.value) : undefined };
      }
      return { question_id: q.id, written_answer_text: document.getElementById(`eq_written_${q.id}`).value.trim() };
    });
    if (!confirm("متأكد إنك عايز تسلّم الامتحان؟ مش هتقدر تعدّل بعد كده.")) return;
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/student/exams/submit", { attempt_id: attemptData.attempt_id, answers });
      toast("تم تسليم الامتحان بنجاح");
      Modal.close();
      loadStudentExams();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "تسليم الامتحان";
    }
  };
}

async function showExamResultModal(attemptId) {
  Modal.open({ title: "نتيجة الامتحان", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" onclick="Modal.close()">إغلاق</button>` });
  try {
    const res = await api.get(NS, "/student/exams/results/show" + qs({ id: attemptId }));
    const a = res.data || res;
    const answers = a.answers || [];
    document.getElementById("modalBody").innerHTML = `
      <div class="card"><div class="card-body">
        <div style="font-weight:700;font-size:18px">الدرجة: ${a.total_score ?? "-"} / ${a.max_score}</div>
        <div class="text-muted">${EXAM_STATUS_BADGES[a.status] || escapeHtml(a.status)}</div>
      </div></div>
      ${answers
        .map(
          (ans, idx) => `<div class="card" style="margin-bottom:8px"><div class="card-body">
            <div style="font-weight:700;margin-bottom:6px">${idx + 1}. ${escapeHtml(ans.question_text || "")}</div>
            ${ans.question_type === "mcq"
              ? (ans.options || []).map((o) => `<div style="${o.is_correct ? "color:var(--success);font-weight:700" : o.id === ans.mcq_option_id ? "color:var(--danger)" : ""}">${o.is_correct ? "✓" : o.id === ans.mcq_option_id ? "✗" : "—"} ${escapeHtml(o.option_text)}</div>`).join("")
              : ans.question_type === "true_false"
                ? `<div>إجابتك: ${ans.true_false_answer ? "صح" : "خطأ"} — الصحيح: ${ans.correct_answer ? "صح" : "خطأ"}</div>`
                : `<div>إجابتك: ${escapeHtml(ans.written_answer_text || "-")}</div>`
            }
            <div class="text-muted" style="font-size:13px;margin-top:6px">الدرجة: ${ans.score_awarded ?? "لسه متصححتش"} / ${ans.max_question_score}${ans.teacher_note ? " — ملاحظة: " + escapeHtml(ans.teacher_note) : ""}</div>
          </div></div>`
        )
        .join("")}`;
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Consultations ================= */
const consultState = { page: 1, perPage: 20 };
const CONS_MEETING_LABELS = { offline: "حضوري", video_call: "مكالمة فيديو", chat: "شات", online: "أونلاين" };

async function renderConsultationsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header"><h3>🗓️ المعلمون المتاحون للاستشارة</h3></div>
      <div id="stConsGrid" class="cards-grid"><div style="text-align:center;padding:40px;grid-column:1/-1"><span class="spinner spinner-dark"></span></div></div>
      <div id="stConsPg"></div>
    </div>`;
  await loadConsultTeachers();
}
async function loadConsultTeachers() {
  const grid = document.getElementById("stConsGrid");
  if (!grid) return;
  try {
    const res = await api.get(NS, "/student/consultations" + qs({ page: consultState.page, limit: consultState.perPage }));
    const { items, page, totalPages, total } = extractList(res);
    if (!items.length) {
      grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">لا يوجد معلمون متاحون للاستشارة حاليًا</div>`;
    } else {
      grid.innerHTML = items
        .map(
          (t) => `<div class="content-card">
            <img src="${escapeHtml(t.image || "")}" onerror="this.style.display='none'">
            <div class="content-card-body">
              <div style="font-weight:700">${escapeHtml(t.name)}</div>
              <div class="text-muted" style="font-size:13px;margin:4px 0">${escapeHtml(t.specialization || "")}</div>
              <div class="text-muted" style="font-size:12.5px">📅 ${t.available_slots_count} مواعيد متاحة</div>
              <button class="btn btn-primary btn-sm btn-block" data-act="view" data-id="${t.id}" data-name="${escapeHtml(t.name)}" style="margin-top:8px">عرض المواعيد</button>
            </div>
          </div>`
        )
        .join("");
      grid.querySelectorAll("[data-act=view]").forEach((b) => (b.onclick = () => openTeacherConsultSlotsModal(Number(b.dataset.id), b.dataset.name)));
    }
    const pgEl = document.getElementById("stConsPg");
    pgEl.innerHTML = paginationHtml(page, totalPages, total, consultState.perPage);
    wirePagination(pgEl, consultState, loadConsultTeachers);
  } catch (err) {
    grid.innerHTML = `<div class="error-box show" style="grid-column:1/-1">${escapeHtml(err.message)}</div>`;
  }
}

async function openTeacherConsultSlotsModal(teacherId, teacherName) {
  Modal.open({ title: `مواعيد ${teacherName}`, size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" id="tcsClose">إغلاق</button>` });
  document.getElementById("tcsClose").onclick = () => Modal.close();
  await loadTeacherConsultSlots(teacherId, teacherName);
}
async function loadTeacherConsultSlots(teacherId, teacherName) {
  try {
    const res = await api.get(NS, "/student/consultations/slots" + qs({ teacher_id: teacherId, limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      document.getElementById("modalBody").innerHTML = `<div class="empty-state">لا توجد مواعيد متاحة</div>`;
      return;
    }
    document.getElementById("modalBody").innerHTML = `
      <div class="table-scroll"><table>
        <thead><tr><th>التاريخ</th><th>الوقت</th><th>نوع اللقاء</th><th></th></tr></thead>
        <tbody>${items
        .map(
          (s) => `<tr>
              <td>${s.slot_date}</td>
              <td>${s.slot_time}</td>
              <td>${CONS_MEETING_LABELS[s.meeting_type] || s.meeting_type}</td>
              <td><button class="btn btn-primary btn-sm" data-act="book" data-id="${s.id}">احجز</button></td>
            </tr>`
        )
        .join("")}</tbody>
      </table></div>`;
    document.querySelectorAll("[data-act=book]").forEach(
      (b) =>
      (b.onclick = () =>
        confirmDialog("هل تريد حجز هذا الموعد؟", async () => {
          await api.post(NS, "/student/consultations/book", { slot_id: Number(b.dataset.id) });
          toast("تم إرسال طلب حجز الموعد بنجاح وفي انتظار موافقة المعلم");
          Modal.close();
          loadConsultTeachers();
        }))
    );
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Academic Reports ================= */
const reportTypeMapStudent = {
  academic_performance: { label: "أداء أكاديمي", class: "badge-primary" },
  behavior_discipline: { label: "سلوك وانضباط", class: "badge-purple" },
  exam_result: { label: "نتيجة امتحان", class: "badge-warning" },
  attendance: { label: "حضور وغياب", class: "badge-success" },
};

async function renderReportsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header"><h3>📊 تقاريري الأكاديمية</h3></div>
      <div id="stReportsBox" style="padding:16px"><div style="text-align:center;padding:40px"><span class="spinner spinner-dark"></span></div></div>
    </div>`;
  try {
    const res = await api.get(NS, "/student/reports");
    const items = extractList(res).items;
    const box = document.getElementById("stReportsBox");
    if (!items.length) {
      box.innerHTML = `<div class="empty-state">لا توجد تقارير لك حالياً</div>`;
      return;
    }
    box.innerHTML = items
      .map((r) => {
        const typeBadge = reportTypeMapStudent[r.report_type] || { label: r.report_type, class: "badge-secondary" };
        const scoreHtml = r.score !== null ? `<span class="badge badge-success" style="font-size:13px">${escapeHtml(r.grade_label || "")} (${r.score}/${r.max_score || 100})</span>` : "";
        const strengthsHtml = Array.isArray(r.strengths) && r.strengths.length ? `<div style="margin-top:8px;font-size:13px;color:#15803d">✔ <strong>نقاط القوة:</strong> ${escapeHtml(r.strengths.join(" • "))}</div>` : "";
        const improvementsHtml = Array.isArray(r.improvements) && r.improvements.length ? `<div style="margin-top:4px;font-size:13px;color:#b45309">📈 <strong>مجالات التطوير:</strong> ${escapeHtml(r.improvements.join(" • "))}</div>` : "";

        return `<div class="card" style="margin-bottom:16px;border:1px solid #e5e7eb">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
              <div>
                <span class="badge ${typeBadge.class}">${typeBadge.label}</span>
                <span class="text-muted" style="font-size:12px;margin-right:8px">${r.report_date || fmtDate(r.created_at)}</span>
                <h3 style="margin:8px 0 4px 0">${escapeHtml(r.title)}</h3>
                <div class="text-muted" style="font-size:13px">المرسل: <strong>${escapeHtml(r.sender?.name || "-")}</strong> | المادة: <strong>${escapeHtml(r.subject_name || "عام")}</strong></div>
              </div>
              <div>${scoreHtml}</div>
            </div>
            <div style="margin-top:12px;font-size:14px;line-height:1.6">${escapeHtml(r.description || "")}</div>
            ${strengthsHtml}
            ${improvementsHtml}
          </div>
        </div>`;
      })
      .join("");
  } catch (err) {
    document.getElementById("stReportsBox").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ---- Boot ---- */
if (isLoggedIn()) {
  showApp();
} else {
  showAuth();
}
