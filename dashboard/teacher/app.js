/* ==== Najeh Teacher Dashboard ==== */
const NS = "teacher";
let pendingEmail = "";

/* ---------- Response shape normalizer (mirrors admin/app.js) ---------- */
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
  navigate("profile");
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

/* ---- Public (unauthenticated) lookups for the registration form: city select + subjects ---- */
async function fetchPublicCities(page, search) {
  const res = await api.get(NS, "/cities" + qs({ page, limit: 20, search }), false);
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((r) => ({ id: r.id, label: translatedName(r) })), hasMore: p < totalPages };
}

let publicSubjectsCache = null;
async function getPublicSubjectsCache() {
  if (publicSubjectsCache) return publicSubjectsCache;
  try {
    const res = await api.get(NS, "/subjects" + qs({ limit: 100 }), false);
    publicSubjectsCache = extractList(res).items.map((s) => ({ id: s.id, label: translatedName(s) }));
  } catch (e) {
    publicSubjectsCache = [];
  }
  return publicSubjectsCache;
}

async function renderRegisterExtras() {
  document.getElementById("regCityWrap").innerHTML = asyncSelectHtml("regCity", { label: "المدينة" });
  wireAsyncSelect("regCity", fetchPublicCities);
  const subjects = await getPublicSubjectsCache();
  document.getElementById("regSubjectsWrap").outerHTML = `<div id="regSubjectsWrap">${multiselectHtml("regSubjectsBox", subjects, [])}</div>`;
}

/* ---- LOGIN ---- */
document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("loginBtn");
  const errBox = document.getElementById("loginError");
  errBox.classList.remove("show");
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>`;
  try {
    const res = await api.post(NS, "/teacher/auth/login", {
      email: document.getElementById("loginEmail").value.trim(),
      password: document.getElementById("loginPassword").value,
    }, false);
    const d = res.data || res;
    const tokens = d.tokens || d;
    setTokens(NS, { token: tokens.access_token || tokens.token, refresh_token: tokens.refresh_token });
    Store.set("teacher_user", d.teacher || d.user || d);
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
      whatsapp: document.getElementById("regWhatsapp").value.trim(),
      city_id: readAsyncSelect("regCity"),
      specialization: document.getElementById("regSpecialization").value.trim(),
      subject_ids: readMultiselect("regSubjectsBox"),
      image: readImageField("regImage") || undefined,
    };
    await api.post(NS, "/teacher/auth/register", await buildRequestBody(payload, "POST"), false);
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
    await api.post(NS, "/teacher/auth/verify-email", { email: pendingEmail, code: document.getElementById("verifyCode").value.trim() }, false);
    toast("تم تأكيد البريد الإلكتروني بنجاح، بانتظار تفعيل الأدمن لحسابك");
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
    await api.post(NS, "/teacher/auth/resend-otp", { email: pendingEmail }, false);
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
    await api.post(NS, "/teacher/auth/forgot-password", { email }, false);
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
    await api.post(NS, "/teacher/auth/verify-reset-code", { email: pendingEmail, code }, false);
    await api.post(NS, "/teacher/auth/reset-password", {
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

const teacherTitles = {
  profile: "الملف الشخصي",
  courses: "كورساتي",
  units: "الوحدات الدراسية",
  videos: "الفيديوهات",
  books: "الكتب",
  exams: "الامتحانات",
  certificates: "الشهادات",
  homework: "الواجبات",
  sessions: "جدول الحصص",
  consultations: "الاستشارات",
  "subscribed-students": "الطلاب المشتركون",
  reports: "التقارير الأكاديمية",
};
let currentView = "profile";
let customPageRefresh = null;

document.getElementById("navMenu").addEventListener("click", (e) => {
  const item = e.target.closest(".nav-item");
  if (!item) return;
  navigate(item.dataset.view);
  toggleSidebar(false);
});

function navigate(view) {
  currentView = view;
  customPageRefresh = null;
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.toggle("active", n.dataset.view === view));
  document.getElementById("pageTitle").textContent = teacherTitles[view] || "";
  const root = document.getElementById("viewRoot");
  const renderer = {
    profile: renderProfile,
    courses: renderMyCourses,
    units: () => renderContentView(unitsTCfg),
    videos: () => renderContentView(videosTCfg),
    books: () => renderContentView(booksTCfg),
    exams: () => renderContentView(examsTCfg),
    certificates: () => renderContentView(certificatesTCfg),
    homework: renderHomeworkView,
    sessions: renderSessionsView,
    consultations: renderConsultationsView,
    "subscribed-students": renderSubscribedStudents,
    reports: renderReportsTeacherView,
  }[view];
  if (renderer) renderer(root);
}

async function renderProfile(root) {
  root.innerHTML = `<div class="full-loader" style="min-height:200px"><span class="spinner spinner-dark" style="width:26px;height:26px"></span></div>`;
  try {
    const res = await api.get(NS, "/teacher/auth/profile");
    const t = res.data || res;
    Store.set("teacher_user", t);
    document.getElementById("sideUserName").textContent = t.name || "المعلم";
    document.getElementById("sideUserRole").textContent = t.email || "";
    if (t.image) document.getElementById("sideUserImg").src = t.image;

    root.innerHTML = `
      <div class="card">
        <div class="card-header"><h3>بيانات المعلم</h3>
          <span>${t.is_active ? statusBadge(1) : statusBadge(0)} ${verifiedBadge(t.is_email_verified)}</span>
        </div>
        <div class="card-body" style="display:flex;gap:20px;flex-wrap:wrap">
          <img class="avatar-sm" style="width:80px;height:80px" src="${escapeHtml(t.image || "")}" onerror="this.style.visibility='hidden'">
          <div style="flex:1;min-width:220px">
            <div class="field"><label>الاسم</label><div>${escapeHtml(t.name)}</div></div>
            <div class="field"><label>البريد الإلكتروني</label><div>${escapeHtml(t.email)}</div></div>
            <div class="field"><label>واتساب</label><div>${escapeHtml(t.whatsapp || "-")}</div></div>
            <div class="field"><label>التخصص</label><div>${escapeHtml(t.specialization || "-")}</div></div>
            <div class="field"><label>المدينة</label><div>${escapeHtml(translatedName(t.city) || "-")}</div></div>
            <div class="field mb-0"><label>المواد الدراسية</label><div>${(t.subjects || []).map((s) => translatedName(s)).join("، ") || "-"}</div></div>
          </div>
        </div>
      </div>`;
  } catch (err) {
    root.innerHTML = `<div class="card"><div class="card-body error-box show">${escapeHtml(err.message)}</div></div>`;
  }
}

/* ================= My Courses ================= */
async function getMyProfile() {
  let t = Store.get("teacher_user");
  if (t && t.subjects) return t;
  const res = await api.get(NS, "/teacher/auth/profile");
  t = res.data || res;
  Store.set("teacher_user", t);
  return t;
}

async function fetchPublicCourseCategories(page, search) {
  const res = await api.get(NS, "/course-categories" + qs({ page, limit: 20, search }), false);
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((r) => ({ id: r.id, label: translatedName(r) })), hasMore: p < totalPages };
}
async function fetchPublicAcademicYears(page, search) {
  const res = await api.get(NS, "/academic-years" + qs({ page, limit: 20, search }), false);
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((r) => ({ id: r.id, label: translatedName(r) })), hasMore: p < totalPages };
}

const courseState = { page: 1, perPage: 10 };

async function renderMyCourses(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>كورساتي</h3>
        <button class="btn btn-primary btn-sm" id="addCourseBtn">+ كورس جديد</button>
      </div>
      <div id="courseStats"></div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الصورة</th><th>الاسم</th><th>المادة</th><th>السعر</th><th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody id="courseTbody"><tr class="loading-row"><td colspan="6"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
      <div id="coursePg"></div>
    </div>`;
  document.getElementById("addCourseBtn").onclick = () => openCourseForm(null);
  await loadMyCourses();
}

async function loadMyCourses() {
  const tbody = document.getElementById("courseTbody");
  try {
    const res = await api.get(NS, "/teacher/courses" + qs({ page: courseState.page, limit: courseState.perPage }));
    const { items, page, totalPages, total, statistics } = extractList(res);
    courseState.page = page || courseState.page;
    document.getElementById("courseStats").innerHTML = statsBarHtml(statistics);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">لا توجد كورسات بعد</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map(
          (row) => `<tr>
            <td><img class="avatar-sm" src="${escapeHtml(row.image || "")}" onerror="this.style.visibility='hidden'"></td>
            <td>${escapeHtml(row.name_ar || translatedName(row, "ar"))}</td>
            <td>${escapeHtml(translatedName(row.subject, "ar")) || "-"}</td>
            <td>${row.price > 0 ? row.price : "مجاني"}</td>
            <td>${statusBadge(row.is_active)}</td>
            <td class="row-actions">
              <button class="btn btn-outline btn-sm" data-act="edit" data-id="${row.id}">تعديل</button>
              <button class="btn btn-danger btn-sm" data-act="del" data-id="${row.id}">حذف</button>
            </td>
          </tr>`
        )
        .join("");
      tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openCourseForm(items.find((i) => String(i.id) === b.dataset.id))));
      tbody.querySelectorAll("[data-act=del]").forEach(
        (b) =>
          (b.onclick = () =>
            confirmDialog("هل تريد حذف هذا الكورس؟", async () => {
              await api.del(NS, "/teacher/courses", { id: Number(b.dataset.id) });
              toast("تم الحذف بنجاح");
              loadMyCourses();
            }))
      );
    }
    const pgEl = document.getElementById("coursePg");
    pgEl.innerHTML = paginationHtml(courseState.page, totalPages, total, courseState.perPage);
    wirePagination(pgEl, courseState, loadMyCourses);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

async function openCourseForm(row) {
  const isEdit = !!row;
  const me = await getMyProfile();
  const mySubjects = me.subjects || [];

  Modal.open({
    title: isEdit ? "تعديل كورس" : "كورس جديد",
    size: "modal-lg",
    bodyHtml: `
      <div class="grid-2">
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="cf_name_ar" required value="${escapeHtml(row?.name_ar || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية</label><input type="text" id="cf_name_en" value="${escapeHtml(row?.name_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="cf_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="cf_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      <div class="field">
        <label>المادة الدراسية * <span class="text-muted" style="font-weight:400">(من المواد المسندة إليك فقط)</span></label>
        <select id="cf_subject_id" required>
          <option value="">اختر مادة...</option>
          ${mySubjects.map((s) => `<option value="${s.id}" ${row?.subject_id === s.id ? "selected" : ""}>${escapeHtml(translatedName(s, "ar"))}</option>`).join("")}
        </select>
      </div>
      <div class="grid-2">
        ${asyncSelectHtml("cf_category", { label: "التصنيف", currentId: row?.category_id ?? "", currentLabel: row?.category ? translatedName(row.category, "ar") : "" })}
        ${asyncSelectHtml("cf_academic_year", { label: "السنة الدراسية", currentId: row?.academic_year_id ?? "", currentLabel: row?.academic_year ? translatedName(row.academic_year, "ar") : "" })}
      </div>
      <div class="field"><label>السعر (0 = مجاني)</label><input type="number" step="0.01" id="cf_price" value="${row?.price ?? 0}"></div>
      ${imageFieldHtml("cf_image", row?.image || "")}
      <div class="field"><label>مفعّل؟</label><select id="cf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`,
    footerHtml: `<button class="btn btn-outline" id="cfCancel">إلغاء</button><button class="btn btn-primary" id="cfSave">${isEdit ? "حفظ التعديلات" : "إضافة"}</button>`,
  });

  wireImageField("cf_image");
  wireAsyncSelect("cf_category", fetchPublicCourseCategories);
  wireAsyncSelect("cf_academic_year", fetchPublicAcademicYears);

  document.getElementById("cfCancel").onclick = () => Modal.close();
  document.getElementById("cfSave").onclick = async () => {
    const btn = document.getElementById("cfSave");
    const payload = {
      name_ar: document.getElementById("cf_name_ar").value.trim(),
      name_en: document.getElementById("cf_name_en").value.trim(),
      description_ar: document.getElementById("cf_desc_ar").value.trim(),
      description_en: document.getElementById("cf_desc_en").value.trim(),
      subject_id: Number(document.getElementById("cf_subject_id").value) || undefined,
      category_id: readAsyncSelect("cf_category"),
      academic_year_id: readAsyncSelect("cf_academic_year"),
      price: Number(document.getElementById("cf_price").value) || 0,
      image: readImageField("cf_image") || undefined,
      is_active: Number(document.getElementById("cf_is_active").value),
    };
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, "/teacher/courses", await buildRequestBody(payload));
      } else {
        await api.post(NS, "/teacher/courses", await buildRequestBody(payload));
      }
      toast(isEdit ? "تم تحديث الكورس" : "تمت إضافة الكورس بنجاح");
      Modal.close();
      loadMyCourses();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ التعديلات" : "إضافة";
    }
  };
}

/* ================= Generic content CRUD engine (Units/Videos/Books/Exams/Certificates) =================
 * Mirrors the admin dashboard's list+modal pattern, scoped to /teacher/* endpoints - the backend
 * auto-filters GETs to the logged-in teacher's own content and auto-stamps created_by on writes.
 */
async function fetchTeacherCourses(page, search) {
  const res = await api.get(NS, "/teacher/courses" + qs({ page, limit: 20, search }));
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((c) => ({ id: c.id, label: c.name_ar || translatedName(c, "ar") })), hasMore: p < totalPages };
}
async function fetchTeacherUnits(page, search) {
  const res = await api.get(NS, "/teacher/units" + qs({ page, limit: 20, search }));
  const { items, page: p, totalPages } = extractList(res);
  return { items: items.map((u) => ({ id: u.id, label: u.name_ar || u.name })), hasMore: p < totalPages };
}
function mySubjectsOptionsHtml(selectedId) {
  const mySubjects = (Store.get("teacher_user")?.subjects) || [];
  return `<option value="">اختر مادة...</option>${mySubjects
    .map((s) => `<option value="${s.id}" ${String(selectedId) === String(s.id) ? "selected" : ""}>${escapeHtml(translatedName(s, "ar"))}</option>`)
    .join("")}`;
}

const contentTState = {};
function ensureContentTState(key) {
  if (!contentTState[key]) contentTState[key] = { page: 1, perPage: 10 };
  return contentTState[key];
}

async function renderContentView(cfg) {
  const st = ensureContentTState(cfg.key);
  const root = document.getElementById("viewRoot");
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>${cfg.title}</h3>
        <button class="btn btn-primary btn-sm" id="ct_add_${cfg.key}">+ إضافة ${cfg.singular}</button>
      </div>
      <div id="ct_stats_${cfg.key}"></div>
      <div class="table-scroll">
        <table>
          <thead><tr>${cfg.columns.map((c) => `<th>${c.label}</th>`).join("")}<th>إجراءات</th></tr></thead>
          <tbody id="ct_tbody_${cfg.key}"><tr class="loading-row"><td colspan="${cfg.columns.length + 1}"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
      <div id="ct_pg_${cfg.key}"></div>
    </div>`;
  document.getElementById(`ct_add_${cfg.key}`).onclick = () => openContentForm(cfg, null);
  await loadContentList(cfg);
}

async function loadContentList(cfg) {
  const st = ensureContentTState(cfg.key);
  const tbody = document.getElementById(`ct_tbody_${cfg.key}`);
  if (!tbody) return;
  try {
    const res = await api.get(NS, cfg.listPath + qs({ page: st.page, limit: st.perPage }));
    const { items, page, totalPages, total, statistics } = extractList(res);
    st.page = page || st.page;
    document.getElementById(`ct_stats_${cfg.key}`).innerHTML = statsBarHtml(statistics);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}"><div class="empty-state">لا توجد بيانات بعد</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map(
          (row) => `<tr>
            ${cfg.columns.map((c) => `<td>${c.render(row)}</td>`).join("")}
            <td class="row-actions">
              <button class="btn btn-outline btn-sm" data-act="edit" data-id="${row.id}">تعديل</button>
              ${cfg.extraActions ? cfg.extraActions(row) : ""}
              <button class="btn btn-danger btn-sm" data-act="del" data-id="${row.id}">حذف</button>
            </td>
          </tr>`
        )
        .join("");
      tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openContentForm(cfg, items.find((i) => String(i.id) === b.dataset.id))));
      tbody.querySelectorAll("[data-act=del]").forEach(
        (b) =>
          (b.onclick = () =>
            confirmDialog(`هل تريد حذف هذا العنصر (${cfg.singular})؟`, async () => {
              await api.del(NS, cfg.listPath, { id: Number(b.dataset.id) });
              toast("تم الحذف بنجاح");
              loadContentList(cfg);
            }))
      );
      if (cfg.wireExtra) cfg.wireExtra(items);
    }
    const pgEl = document.getElementById(`ct_pg_${cfg.key}`);
    pgEl.innerHTML = paginationHtml(st.page, totalPages, total, st.perPage);
    wirePagination(pgEl, st, () => loadContentList(cfg));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

function openContentForm(cfg, row) {
  const isEdit = !!row;
  Modal.open({
    title: (isEdit ? "تعديل " : "إضافة ") + cfg.singular,
    size: "modal-lg",
    bodyHtml: cfg.formHtml(row),
    footerHtml: `<button class="btn btn-outline" id="ctCancel">إلغاء</button><button class="btn btn-primary" id="ctSave">${isEdit ? "حفظ التعديلات" : "إضافة"}</button>`,
  });
  if (cfg.afterRender) cfg.afterRender(row);
  document.getElementById("ctCancel").onclick = () => Modal.close();
  document.getElementById("ctSave").onclick = async () => {
    const btn = document.getElementById("ctSave");
    try {
      const payload = cfg.collect(row);
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, cfg.listPath, await buildRequestBody(payload));
      } else {
        await api.post(NS, cfg.listPath, await buildRequestBody(payload));
      }
      toast(isEdit ? "تم تحديث البيانات" : "تمت الإضافة بنجاح");
      Modal.close();
      loadContentList(cfg);
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ التعديلات" : "إضافة";
    }
  };
}

/* Shared linkage fields (unit/course/subject) for units/videos/books/exams forms */
function tContentLinkageHtml(row) {
  return `
    <div class="grid-2">
      ${asyncSelectHtml("cf_unit", { label: "الوحدة (اختياري)", currentId: row?.unit_id ?? "", currentLabel: row?.unit ? (row.unit.name_ar || row.unit.name) : "" })}
      ${asyncSelectHtml("cf_course", { label: "الكورس", currentId: row?.course_id ?? "", currentLabel: row?.course ? (row.course.name_ar || translatedName(row.course, "ar")) : "" })}
    </div>
    <div class="field"><label>المادة الدراسية *</label><select id="cf_subject_id" required>${mySubjectsOptionsHtml(row?.subject_id)}</select></div>`;
}
function wireTContentLinkage() {
  wireAsyncSelect("cf_unit", fetchTeacherUnits);
  wireAsyncSelect("cf_course", fetchTeacherCourses);
}
function collectTContentLinkage() {
  return {
    unit_id: readAsyncSelect("cf_unit"),
    course_id: readAsyncSelect("cf_course"),
    subject_id: Number(document.getElementById("cf_subject_id").value) || undefined,
  };
}

/* ---------- Units ---------- */
const unitsTCfg = {
  key: "units",
  title: "الوحدات الدراسية",
  singular: "وحدة",
  listPath: "/teacher/units",
  columns: [
    { label: "الاسم", render: (r) => escapeHtml(r.name_ar || r.name) },
    { label: "الكود", render: (r) => escapeHtml(r.code || "-") },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="cf_name_ar" required value="${escapeHtml(row?.name_ar || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية</label><input type="text" id="cf_name_en" value="${escapeHtml(row?.name_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="cf_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="cf_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الكود</label><input type="text" id="cf_code" value="${escapeHtml(row?.code || "")}"></div>
        ${asyncSelectHtml("cf_course", { label: "الكورس *", required: true, currentId: row?.course_id ?? "", currentLabel: row?.course ? (row.course.name_ar || translatedName(row.course, "ar")) : "" })}
      </div>
      <div class="field"><label>المادة الدراسية *</label><select id="cf_subject_id" required>${mySubjectsOptionsHtml(row?.subject_id)}</select></div>
      <div class="field"><label>مفعّل؟</label><select id="cf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireAsyncSelect("cf_course", fetchTeacherCourses);
  },
  collect() {
    return {
      name_ar: document.getElementById("cf_name_ar").value.trim(),
      name_en: document.getElementById("cf_name_en").value.trim(),
      description_ar: document.getElementById("cf_desc_ar").value.trim(),
      description_en: document.getElementById("cf_desc_en").value.trim(),
      code: document.getElementById("cf_code").value.trim(),
      course_id: readAsyncSelect("cf_course"),
      subject_id: Number(document.getElementById("cf_subject_id").value) || undefined,
      is_active: Number(document.getElementById("cf_is_active").value),
    };
  },
};

/* ---------- Videos ---------- */
const videosTCfg = {
  key: "videos",
  title: "الفيديوهات",
  singular: "فيديو",
  listPath: "/teacher/videos",
  columns: [
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title) },
    { label: "المدة", render: (r) => escapeHtml(r.duration || "-") },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="cf_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="cf_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="cf_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="cf_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      ${tContentLinkageHtml(row)}
      <div class="grid-2">
        <div class="field"><label>رابط الفيديو</label><input type="text" id="cf_video_url" value="${escapeHtml(row?.video_url || "")}" placeholder="https://..."></div>
        <div class="field"><label>المدة (مثال: 12:30)</label><input type="text" id="cf_duration" value="${escapeHtml(row?.duration || "")}"></div>
      </div>
      <div class="field"><label>مفعّل؟</label><select id="cf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireTContentLinkage();
  },
  collect() {
    return {
      title_ar: document.getElementById("cf_title_ar").value.trim(),
      title_en: document.getElementById("cf_title_en").value.trim(),
      description_ar: document.getElementById("cf_desc_ar").value.trim(),
      description_en: document.getElementById("cf_desc_en").value.trim(),
      ...collectTContentLinkage(),
      video_url: document.getElementById("cf_video_url").value.trim(),
      duration: document.getElementById("cf_duration").value.trim(),
      is_active: Number(document.getElementById("cf_is_active").value),
    };
  },
};

/* ---------- Books ---------- */
const booksTCfg = {
  key: "books",
  title: "الكتب",
  singular: "كتاب",
  listPath: "/teacher/books",
  columns: [
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title) },
    { label: "النوع", render: (r) => escapeHtml(r.file_type || "-") },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="cf_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="cf_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="cf_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="cf_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      ${tContentLinkageHtml(row)}
      <div class="field"><label>نوع الملف</label><input type="text" id="cf_file_type" value="${escapeHtml(row?.file_type || "pdf")}"></div>
      ${imageFieldHtml("cf_file", row?.link || row?.file_path || "", { kind: "file", label: "ملف الكتاب (رابط أو رفع)" })}
      <div class="field"><label>مفعّل؟</label><select id="cf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireTContentLinkage();
    wireImageField("cf_file");
  },
  collect() {
    return {
      title_ar: document.getElementById("cf_title_ar").value.trim(),
      title_en: document.getElementById("cf_title_en").value.trim(),
      description_ar: document.getElementById("cf_desc_ar").value.trim(),
      description_en: document.getElementById("cf_desc_en").value.trim(),
      ...collectTContentLinkage(),
      file_type: document.getElementById("cf_file_type").value.trim(),
      file: readImageField("cf_file") || undefined,
      is_active: Number(document.getElementById("cf_is_active").value),
    };
  },
};

/* ---------- Exams ---------- */
const examsTCfg = {
  key: "exams",
  title: "الامتحانات",
  singular: "امتحان",
  listPath: "/teacher/exams",
  columns: [
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title) },
    { label: "المدة (دقيقة)", render: (r) => r.duration_minutes ?? "-" },
    { label: "الدرجة الكلية", render: (r) => r.total_marks ?? "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  extraActions: (row) => `
    <button class="btn btn-outline btn-sm" data-act="questions" data-id="${row.id}">❓ الأسئلة</button>
    <button class="btn btn-outline btn-sm" data-act="results" data-id="${row.id}">📊 النتائج</button>`,
  wireExtra(items) {
    document.querySelectorAll(`#ct_tbody_exams [data-act=questions]`).forEach(
      (b) => (b.onclick = () => showTExamQuestionsPage(items.find((i) => String(i.id) === b.dataset.id)))
    );
    document.querySelectorAll(`#ct_tbody_exams [data-act=results]`).forEach(
      (b) => (b.onclick = () => showTExamAttemptsPage(items.find((i) => String(i.id) === b.dataset.id)))
    );
  },
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="cf_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="cf_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="cf_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="cf_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      ${tContentLinkageHtml(row)}
      <div class="grid-2">
        <div class="field"><label>المدة (دقيقة)</label><input type="number" id="cf_duration_minutes" value="${row?.duration_minutes ?? 30}"></div>
        <div class="field"><label>الدرجة الكلية</label><input type="number" id="cf_total_marks" value="${row?.total_marks ?? 100}"></div>
      </div>
      <div class="field"><label>درجة النجاح</label><input type="number" id="cf_pass_marks" value="${row?.pass_marks ?? 50}"></div>
      <div class="field"><label>مفعّل؟</label><select id="cf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireTContentLinkage();
  },
  collect() {
    return {
      title_ar: document.getElementById("cf_title_ar").value.trim(),
      title_en: document.getElementById("cf_title_en").value.trim(),
      description_ar: document.getElementById("cf_desc_ar").value.trim(),
      description_en: document.getElementById("cf_desc_en").value.trim(),
      ...collectTContentLinkage(),
      duration_minutes: Number(document.getElementById("cf_duration_minutes").value) || 30,
      total_marks: Number(document.getElementById("cf_total_marks").value) || 100,
      pass_marks: Number(document.getElementById("cf_pass_marks").value) || 50,
      is_active: Number(document.getElementById("cf_is_active").value),
    };
  },
};

/* ---------- Certificates ---------- */
const certificatesTCfg = {
  key: "certificates",
  title: "الشهادات",
  singular: "شهادة",
  listPath: "/teacher/certificates",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name_ar || r.name) },
    { label: "النوع", render: (r) => escapeHtml(r.certificate_type || "-") },
    { label: "الدرجة المطلوبة", render: (r) => r.score ?? "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="cf_name_ar" required value="${escapeHtml(row?.name_ar || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية</label><input type="text" id="cf_name_en" value="${escapeHtml(row?.name_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>نوع الشهادة</label><input type="text" id="cf_cert_type" value="${escapeHtml(row?.certificate_type || "general")}"></div>
        <div class="field"><label>الدرجة المطلوبة</label><input type="number" step="0.5" id="cf_score" value="${row?.score ?? 0}"></div>
      </div>
      <div class="field"><label>المادة الدراسية</label><select id="cf_subject_id">${mySubjectsOptionsHtml(row?.subject_id)}</select></div>
      ${asyncSelectHtml("cf_course", { label: "الكورس", currentId: row?.course_id ?? "", currentLabel: row?.course ? (row.course.name_ar || translatedName(row.course, "ar")) : "" })}
      ${imageFieldHtml("cf_image", row?.image || "")}
      <div class="field"><label>مفعّل؟</label><select id="cf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireImageField("cf_image");
    wireAsyncSelect("cf_course", fetchTeacherCourses);
  },
  collect() {
    return {
      name_ar: document.getElementById("cf_name_ar").value.trim(),
      name_en: document.getElementById("cf_name_en").value.trim(),
      certificate_type: document.getElementById("cf_cert_type").value.trim() || "general",
      score: Number(document.getElementById("cf_score").value) || 0,
      subject_id: Number(document.getElementById("cf_subject_id").value) || undefined,
      course_id: readAsyncSelect("cf_course"),
      image: readImageField("cf_image") || undefined,
      is_active: Number(document.getElementById("cf_is_active").value),
    };
  },
};

/* ---------- Exam Questions (drill-down page, teacher-scoped) ---------- */
function showTExamQuestionsPage(exam) {
  currentView = "exam-questions";
  customPageRefresh = () => renderTExamQuestionsPage(document.getElementById("viewRoot"), exam);
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
  document.getElementById("pageTitle").textContent = `أسئلة الامتحان: ${exam.title_ar || exam.title}`;
  renderTExamQuestionsPage(document.getElementById("viewRoot"), exam);
}
function questionTypeLabel(type) {
  return { mcq: "اختيار من متعدد", true_false: "صح / خطأ", written: "مقالي" }[type] || type || "-";
}
function renderTExamQuestionsPage(root, exam) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" id="backToExams">→ رجوع للامتحانات</button>
          <h3 class="mb-0">أسئلة الامتحان: ${escapeHtml(exam.title_ar || exam.title)}</h3>
        </div>
        <button class="btn btn-primary btn-sm" id="addQuestionBtn">+ سؤال جديد</button>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>السؤال</th><th>النوع</th><th>الدرجة</th><th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody id="eqTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("backToExams").onclick = () => navigate("exams");
  document.getElementById("addQuestionBtn").onclick = () => openTQuestionForm(null, { exam_id: exam.id, label: exam.title_ar || exam.title });
  loadTExamQuestions(exam);
}
async function loadTExamQuestions(exam) {
  const tbody = document.getElementById("eqTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/teacher/questions" + qs({ exam_id: exam.id, limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد أسئلة لهذا الامتحان بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (q) => `<tr>
          <td>${escapeHtml((q.question_text_ar || q.question_text || "").slice(0, 70))}</td>
          <td>${questionTypeLabel(q.question_type)}</td>
          <td>${q.score}</td>
          <td>${statusBadge(q.is_active)}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${q.id}">تعديل</button>
            <button class="btn btn-danger btn-sm" data-act="del" data-id="${q.id}">حذف</button>
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openTQuestionForm(items.find((i) => String(i.id) === b.dataset.id), null)));
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذا السؤال؟", async () => {
            await api.del(NS, "/teacher/questions", { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadTExamQuestions(exam);
          }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

let mcqOptionUidCounter = 0;
function mcqOptionRowHtml(opt) {
  const uid = mcqOptionUidCounter++;
  return `<div class="mcq-option-row" data-uid="${uid}" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
    <input type="radio" name="qf_correct" value="${uid}" ${opt?.is_correct ? "checked" : ""} title="الإجابة الصحيحة">
    <input type="text" class="opt-ar" placeholder="نص الخيار (عربي)" value="${escapeHtml(opt?.option_text_ar || "")}" style="flex:1">
    <input type="text" class="opt-en" placeholder="نص الخيار (إنجليزي)" value="${escapeHtml(opt?.option_text_en || "")}" style="flex:1">
    <button type="button" class="btn btn-danger btn-sm remove-opt">×</button>
  </div>`;
}
function wireMcqOptionRemoveButtons(box) {
  box.querySelectorAll(".remove-opt").forEach(
    (btn) =>
      (btn.onclick = () => {
        if (box.children.length <= 2) return toast("لازم يبقى فيه خيارين على الأقل", "error");
        btn.closest(".mcq-option-row").remove();
      })
  );
}
function wireMcqOptionsEditor() {
  const box = document.getElementById("qf_options");
  mcqOptionUidCounter = 0;
  box.innerHTML = [{}, {}].map(mcqOptionRowHtml).join("");
  wireMcqOptionRemoveButtons(box);
  document.getElementById("qf_addOption").onclick = () => {
    box.insertAdjacentHTML("beforeend", mcqOptionRowHtml());
    wireMcqOptionRemoveButtons(box);
  };
}
function collectMcqOptions() {
  const rows = document.querySelectorAll("#qf_options .mcq-option-row");
  const checkedUid = document.querySelector('input[name="qf_correct"]:checked')?.value;
  return Array.from(rows).map((row) => ({
    option_text_ar: row.querySelector(".opt-ar").value.trim(),
    option_text_en: row.querySelector(".opt-en").value.trim(),
    is_correct: row.dataset.uid === checkedUid ? 1 : 0,
  }));
}
function questionTypeSectionHtml(type) {
  if (type === "mcq") {
    return `<div class="field"><label>خيارات الإجابة (اختر الدائرة بجانب الإجابة الصحيحة)</label><div id="qf_options"></div><button type="button" class="btn btn-outline btn-sm" id="qf_addOption">+ إضافة خيار</button></div>`;
  }
  if (type === "true_false") {
    return `<div class="field"><label>الإجابة الصحيحة</label><select id="qf_tf_answer"><option value="1">صح</option><option value="0">خطأ</option></select></div>`;
  }
  return `<div class="grid-2"><div class="field"><label>الإجابة النموذجية بالعربية</label><textarea id="qf_model_ar" rows="2"></textarea></div><div class="field"><label>الإجابة النموذجية بالإنجليزية</label><textarea id="qf_model_en" rows="2"></textarea></div></div>`;
}
function questionAnswerReadonlyHtml(q) {
  if (q.question_type === "mcq") {
    return `<div class="field"><label>الخيارات (لا يمكن تعديل الإجابات بعد الإنشاء)</label>
      <div style="border:1px solid var(--border);border-radius:8px;padding:6px">
        ${(q.options || [])
          .map((o) => `<div style="padding:6px 8px;${o.is_correct ? "color:var(--success);font-weight:700" : ""}">${o.is_correct ? "✓ " : "— "}${escapeHtml(o.option_text_ar || o.option_text || "")}</div>`)
          .join("") || `<div class="text-muted" style="padding:6px 8px">لا توجد خيارات</div>`}
      </div></div>`;
  }
  if (q.question_type === "true_false") {
    return `<div class="field"><label>الإجابة الصحيحة (لا يمكن تعديلها بعد الإنشاء)</label><div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${q.correct_answer ? "صح" : "خطأ"}</div></div>`;
  }
  return `<div class="field"><label>الإجابة النموذجية (لا يمكن تعديلها بعد الإنشاء)</label><div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${escapeHtml(q.model_answer_ar || q.model_answer || "-")}</div></div>`;
}
function openTQuestionForm(question, presetLinkage) {
  const isEdit = !!question;
  const type = question?.question_type || "mcq";
  Modal.open({
    title: isEdit ? "تعديل سؤال" : "سؤال جديد",
    size: "modal-lg",
    bodyHtml: `
      ${
        presetLinkage
          ? `<div class="field"><label>الامتحان</label><div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">📝 ${escapeHtml(presetLinkage.label)} <span class="text-muted" style="font-size:12px">(محدد تلقائيًا)</span></div></div>`
          : ""
      }
      <div class="grid-2">
        <div class="field">
          <label>نوع السؤال *</label>
          ${
            isEdit
              ? `<div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${questionTypeLabel(type)}</div>`
              : `<select id="qf_type">
                  <option value="mcq">اختيار من متعدد</option>
                  <option value="true_false">صح / خطأ</option>
                  <option value="written">مقالي</option>
                </select>`
          }
        </div>
        <div class="field"><label>الدرجة</label><input type="number" step="0.5" id="qf_score" value="${question?.score ?? 1}"></div>
      </div>
      <div class="field"><label>نص السؤال بالعربية *</label><textarea id="qf_text_ar" rows="2" required>${escapeHtml(question?.question_text_ar || question?.question_text || "")}</textarea></div>
      <div class="field"><label>نص السؤال بالإنجليزية</label><textarea id="qf_text_en" rows="2">${escapeHtml(question?.question_text_en || "")}</textarea></div>
      <div id="qf_type_section">${isEdit ? questionAnswerReadonlyHtml(question) : questionTypeSectionHtml(type)}</div>
      <div class="field"><label>مفعّل؟</label><select id="qf_is_active"><option value="1" ${question?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${question?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`,
    footerHtml: `<button class="btn btn-outline" id="qfCancel">إلغاء</button><button class="btn btn-primary" id="qfSave">${isEdit ? "حفظ التعديلات" : "إضافة"}</button>`,
  });
  if (!isEdit) {
    wireMcqOptionsEditor();
    document.getElementById("qf_type").addEventListener("change", (e) => {
      document.getElementById("qf_type_section").innerHTML = questionTypeSectionHtml(e.target.value);
      if (e.target.value === "mcq") wireMcqOptionsEditor();
    });
  }
  document.getElementById("qfCancel").onclick = () => Modal.close();
  document.getElementById("qfSave").onclick = async () => {
    const btn = document.getElementById("qfSave");
    const payload = {
      question_text_ar: document.getElementById("qf_text_ar").value.trim(),
      question_text_en: document.getElementById("qf_text_en").value.trim(),
      score: Number(document.getElementById("qf_score").value) || 1,
      is_active: Number(document.getElementById("qf_is_active").value),
    };
    if (isEdit) {
      payload.id = question.id;
    } else {
      payload.question_type = document.getElementById("qf_type").value;
      if (presetLinkage?.exam_id) payload.exam_id = presetLinkage.exam_id;
      if (payload.question_type === "mcq") {
        payload.mcq_options = collectMcqOptions();
        if (payload.mcq_options.length < 2) return toast("لازم يبقى فيه خيارين على الأقل", "error");
        if (!payload.mcq_options.some((o) => o.is_correct)) return toast("حدد الإجابة الصحيحة", "error");
      } else if (payload.question_type === "true_false") {
        payload.correct_answer = Number(document.getElementById("qf_tf_answer").value);
      } else if (payload.question_type === "written") {
        payload.model_answer_ar = document.getElementById("qf_model_ar").value.trim();
        payload.model_answer_en = document.getElementById("qf_model_en").value.trim();
      }
    }
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) await api.put(NS, "/teacher/questions", payload);
      else await api.post(NS, "/teacher/questions", payload);
      toast(isEdit ? "تم التحديث" : "تمت الإضافة بنجاح");
      Modal.close();
      if (customPageRefresh) customPageRefresh();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ التعديلات" : "إضافة";
    }
  };
}

/* ---------- Subscribed students (read-only) ---------- */
async function renderSubscribedStudents(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header"><h3>الطلاب المشتركون في موادي/كورساتي</h3></div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الاسم</th><th>البريد</th><th>نوع الاشتراك</th><th>ينتهي في</th><th>إجراءات</th></tr></thead>
          <tbody id="ssTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  const tbody = document.getElementById("ssTbody");
  try {
    const res = await api.get(NS, "/teacher/subscribed-students" + qs({ limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا يوجد طلاب مشتركون بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map((s) => {
        const studentId = s.student?.id || s.id;
        const studentName = s.student?.name || s.name || "-";
        return `<tr>
          <td>${escapeHtml(studentName)}</td>
          <td>${escapeHtml(s.student?.email || s.email || "-")}</td>
          <td>${escapeHtml(s.subscription_type || "-")}</td>
          <td>${s.end_date ? fmtDate(s.end_date) : "بدون تاريخ انتهاء"}</td>
          <td class="row-actions"><button class="btn btn-outline btn-sm" data-act="reports" data-id="${studentId}" data-name="${escapeHtml(studentName)}">📝 التقارير</button></td>
        </tr>`;
      })
      .join("");
    tbody.querySelectorAll("[data-act=reports]").forEach((b) => (b.onclick = () => openStudentReportsModal(Number(b.dataset.id), b.dataset.name)));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

/* ================= Student Reports ================= */
const reportTypeMapTeacher = {
  academic_performance: { label: "أداء أكاديمي", class: "badge-primary" },
  behavior_discipline: { label: "سلوك وانضباط", class: "badge-purple" },
  exam_result: { label: "نتيجة امتحان", class: "badge-warning" },
  attendance: { label: "حضور وغياب", class: "badge-success" },
};

async function openStudentReportsModal(studentId, studentName) {
  Modal.open({
    title: `تقارير الطالب: ${studentName}`,
    size: "modal-lg",
    bodyHtml: `
      <div id="srList" style="margin-bottom:16px"><div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div></div>
      <div class="card"><div class="card-body">
        <h4 style="margin-top:0">إضافة تقرير أكاديمي جديد</h4>
        <div class="field"><label>نوع التقرير *</label>
          <select id="sr_report_type">
            <option value="academic_performance">أداء أكاديمي</option>
            <option value="behavior_discipline">سلوك وانضباط</option>
            <option value="exam_result">نتيجة امتحان</option>
            <option value="attendance">حضور وغياب</option>
          </select>
        </div>
        <div class="field"><label>العنوان *</label><input type="text" id="sr_title" placeholder="مثال: تقرير الأداء الأكاديمي - الفصل الأول" required></div>
        <div class="field"><label>الفصل الدراسي</label><input type="text" id="sr_term" value="الفصل الدراسي الأول"></div>
        <div class="form-row">
          <div class="field"><label>الدرجة المكتسبة</label><input type="number" step="0.1" id="sr_score" placeholder="95"></div>
          <div class="field"><label>الدرجة الكلية</label><input type="number" step="0.1" id="sr_max_score" value="100"></div>
        </div>
        <div class="field"><label>التقدير</label><input type="text" id="sr_grade_label" placeholder="ممتاز / جيد جداً"></div>
        <div class="field"><label>ملاحظات المعلم / الوصف *</label><textarea id="sr_description" rows="3" required></textarea></div>
        <div class="field"><label>نقاط القوة (مفصولة بفواصل)</label><input type="text" id="sr_strengths" placeholder="مثال: حل المسائل التحليلية, المشاركة الفعالة"></div>
        <div class="field"><label>مجالات التطوير (مفصولة بفواصل)</label><input type="text" id="sr_improvements" placeholder="مثال: التركيز أكثر في الإحصاء"></div>
        <div class="field"><label>تاريخ التقرير</label><input type="date" id="sr_date" value="${new Date().toISOString().split('T')[0]}"></div>
        <button class="btn btn-primary btn-block" id="srSave">إضافة التقرير</button>
      </div></div>`,
    footerHtml: `<button class="btn btn-outline" id="srClose">إغلاق</button>`,
  });
  document.getElementById("srClose").onclick = () => Modal.close();
  document.getElementById("srSave").onclick = async () => {
    const btn = document.getElementById("srSave");
    const title = document.getElementById("sr_title").value.trim();
    const description = document.getElementById("sr_description").value.trim();
    if (!title) return toast("عنوان التقرير مطلوب", "error");

    const parseTags = (str) => str.split(",").map((s) => s.trim()).filter(Boolean);
    const payload = {
      student_id: studentId,
      report_type: document.getElementById("sr_report_type").value,
      title: title,
      term: document.getElementById("sr_term").value.trim(),
      score: document.getElementById("sr_score").value !== "" ? Number(document.getElementById("sr_score").value) : undefined,
      max_score: document.getElementById("sr_max_score").value !== "" ? Number(document.getElementById("sr_max_score").value) : undefined,
      grade_label: document.getElementById("sr_grade_label").value.trim(),
      description: description,
      strengths: parseTags(document.getElementById("sr_strengths").value),
      improvements: parseTags(document.getElementById("sr_improvements").value),
      report_date: document.getElementById("sr_date").value,
    };

    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/teacher/reports", payload);
      toast("تمت إضافة التقرير بنجاح");
      document.getElementById("sr_title").value = "";
      document.getElementById("sr_description").value = "";
      document.getElementById("sr_strengths").value = "";
      document.getElementById("sr_improvements").value = "";
      loadStudentReports(studentId);
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
    } finally {
      btn.disabled = false;
      btn.textContent = "إضافة التقرير";
    }
  };
  loadStudentReports(studentId);
}

async function loadStudentReports(studentId) {
  const box = document.getElementById("srList");
  if (!box) return;
  try {
    const res = await api.get(NS, "/teacher/reports" + qs({ student_id: studentId }));
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      box.innerHTML = `<div class="empty-state">لا توجد تقارير بعد</div>`;
      return;
    }
    box.innerHTML = list
      .map(
        (r) => {
          const typeBadge = reportTypeMapTeacher[r.report_type] || { label: r.report_type, class: "badge-secondary" };
          const scoreHtml = r.score !== null ? `<span class="badge badge-success">${escapeHtml(r.grade_label || "")} ${r.score}/${r.max_score || 100}</span>` : "";
          const strengthsHtml = Array.isArray(r.strengths) && r.strengths.length ? `<div style="margin-top:6px;font-size:12px;color:green">✔ ${escapeHtml(r.strengths.join(" • "))}</div>` : "";
          const improvementsHtml = Array.isArray(r.improvements) && r.improvements.length ? `<div style="margin-top:4px;font-size:12px;color:#d97706">📈 ${escapeHtml(r.improvements.join(" • "))}</div>` : "";

          return `<div class="card" style="margin-bottom:12px">
            <div class="card-body">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                <div>
                  <span class="badge ${typeBadge.class}">${typeBadge.label}</span>
                  <span class="text-muted" style="font-size:11.5px;margin-right:8px">${r.report_date || fmtDate(r.created_at)}</span>
                  <h4 style="margin:8px 0 4px 0">${escapeHtml(r.title)}</h4>
                  <div class="text-muted" style="font-size:13px">${escapeHtml(r.description || "")}</div>
                  ${scoreHtml}
                  ${strengthsHtml}
                  ${improvementsHtml}
                </div>
                <button class="btn btn-danger btn-sm" data-act="del-report" data-id="${r.id}">حذف</button>
              </div>
            </div>
          </div>`;
        }
      )
      .join("");
    box.querySelectorAll("[data-act=del-report]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذا التقرير؟", async () => {
            await api.del(NS, "/teacher/reports", { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadStudentReports(studentId);
          }))
    );
  } catch (err) {
    box.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

async function renderReportsTeacherView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>📊 جميع التقارير الأكاديمية الصادرة</h3>
      </div>
      <div id="tReportsList" style="padding:16px"><div style="text-align:center;padding:30px"><span class="spinner spinner-dark"></span></div></div>
    </div>`;
  try {
    const res = await api.get(NS, "/teacher/reports");
    const items = extractList(res).items;
    const box = document.getElementById("tReportsList");
    if (!items.length) {
      box.innerHTML = `<div class="empty-state">لم تقم بإضافة أي تقارير أكاديمية للطلاب بعد</div>`;
      return;
    }
    box.innerHTML = items
      .map((r) => {
        const typeBadge = reportTypeMapTeacher[r.report_type] || { label: r.report_type, class: "badge-secondary" };
        const scoreHtml = r.score !== null ? `<span class="badge badge-success">${escapeHtml(r.grade_label || "")} ${r.score}/${r.max_score || 100}</span>` : "";

        return `<div class="card" style="margin-bottom:12px">
          <div class="card-body" style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
            <div>
              <span class="badge ${typeBadge.class}">${typeBadge.label}</span>
              <span class="text-muted" style="font-size:11.5px;margin-right:8px">${r.report_date || fmtDate(r.created_at)}</span>
              <h4 style="margin:8px 0 4px 0">${escapeHtml(r.title)} — <small>الطالب: ${escapeHtml(r.student_name || `#${r.student_id}`)}</small></h4>
              <div class="text-muted" style="font-size:13px">${escapeHtml(r.description || "")}</div>
              ${scoreHtml}
            </div>
            <button class="btn btn-danger btn-sm" data-act="del-report-view" data-id="${r.id}">حذف</button>
          </div>
        </div>`;
      })
      .join("");

    box.querySelectorAll("[data-act=del-report-view]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذا التقرير؟", async () => {
            await api.del(NS, "/teacher/reports", { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            renderReportsTeacherView(root);
          }))
    );
  } catch (err) {
    document.getElementById("tReportsList").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Homework ================= */
async function renderHomeworkView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>الواجبات</h3>
        <button class="btn btn-primary btn-sm" id="addHwBtn">+ واجب جديد</button>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>العنوان</th><th>المادة</th><th>آخر ميعاد للتسليم</th><th>عدد التسليمات</th><th>إجراءات</th></tr></thead>
          <tbody id="hwTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("addHwBtn").onclick = () => openHomeworkForm(null);
  await loadHomeworkList();
}

async function loadHomeworkList() {
  const tbody = document.getElementById("hwTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/teacher/homework" + qs({ limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد واجبات بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (h) => `<tr>
          <td>${escapeHtml(h.title)}</td>
          <td>${escapeHtml(translatedName(h.subject, "ar") || "-")}</td>
          <td>${h.due_date || "-"}</td>
          <td>${h.submissions_count ?? 0}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-sm" data-act="submissions" data-id="${h.id}">📥 التسليمات</button>
            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${h.id}">تعديل</button>
            <button class="btn btn-danger btn-sm" data-act="del" data-id="${h.id}">حذف</button>
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openHomeworkForm(items.find((i) => String(i.id) === b.dataset.id))));
    tbody.querySelectorAll("[data-act=submissions]").forEach((b) => (b.onclick = () => openHomeworkSubmissionsModal(items.find((i) => String(i.id) === b.dataset.id))));
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذا الواجب؟", async () => {
            await api.del(NS, "/teacher/homework", { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadHomeworkList();
          }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

function openHomeworkForm(row) {
  const isEdit = !!row;
  Modal.open({
    title: isEdit ? "تعديل واجب" : "واجب جديد",
    bodyHtml: `
      <div class="field"><label>المادة الدراسية *</label><select id="hf_subject_id" required>${mySubjectsOptionsHtml(row?.subject?.id)}</select></div>
      <div class="field"><label>العنوان *</label><input type="text" id="hf_title" required value="${escapeHtml(row?.title || "")}"></div>
      <div class="field"><label>الوصف</label><textarea id="hf_description" rows="2">${escapeHtml(row?.description || "")}</textarea></div>
      <div class="field"><label>آخر ميعاد للتسليم</label><input type="date" id="hf_due_date" value="${row?.due_date || ""}"></div>
      ${imageFieldHtml("hf_file", row?.file_path || "", { kind: "file", label: "ملف الواجب (رابط أو رفع)" })}
      <div class="field"><label>مفعّل؟</label><select id="hf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`,
    footerHtml: `<button class="btn btn-outline" id="hfCancel">إلغاء</button><button class="btn btn-primary" id="hfSave">${isEdit ? "حفظ التعديلات" : "إضافة"}</button>`,
  });
  wireImageField("hf_file");
  document.getElementById("hfCancel").onclick = () => Modal.close();
  document.getElementById("hfSave").onclick = async () => {
    const btn = document.getElementById("hfSave");
    const payload = {
      subject_id: Number(document.getElementById("hf_subject_id").value) || undefined,
      title: document.getElementById("hf_title").value.trim(),
      description: document.getElementById("hf_description").value.trim(),
      due_date: document.getElementById("hf_due_date").value || undefined,
      file: readImageField("hf_file") || undefined,
      is_active: Number(document.getElementById("hf_is_active").value),
    };
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, "/teacher/homework", await buildRequestBody(payload));
      } else {
        await api.post(NS, "/teacher/homework", await buildRequestBody(payload));
      }
      toast(isEdit ? "تم التحديث" : "تمت الإضافة بنجاح");
      Modal.close();
      loadHomeworkList();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ التعديلات" : "إضافة";
    }
  };
}

async function openHomeworkSubmissionsModal(homework) {
  Modal.open({ title: `تسليمات: ${homework.title}`, size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" id="hsClose">إغلاق</button>` });
  document.getElementById("hsClose").onclick = () => Modal.close();
  try {
    const res = await api.get(NS, "/teacher/homework/submissions" + qs({ homework_id: homework.id }));
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      document.getElementById("modalBody").innerHTML = `<div class="empty-state">لا توجد تسليمات بعد</div>`;
      return;
    }
    document.getElementById("modalBody").innerHTML = list
      .map(
        (s) => `<div class="card" style="margin-bottom:10px">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
              <div><b>${escapeHtml(s.student_name)}</b> <span class="text-muted">— ${escapeHtml(s.student_email)}</span></div>
              ${s.status === "reviewed" ? `<span class="badge badge-success">تم التقييم</span>` : `<span class="badge badge-warning">بانتظار التقييم</span>`}
            </div>
            <a href="${escapeHtml(s.file_path)}" target="_blank" rel="noopener" class="chip" style="margin-bottom:10px">📄 عرض ملف الحل</a>
            ${s.feedback_text ? `<div class="text-muted" style="font-size:13px;margin-bottom:8px">فيدباكك: ${escapeHtml(s.feedback_text)} ${s.feedback_rating ? "(" + s.feedback_rating + "/5)" : ""}</div>` : ""}
            <div class="grid-2">
              <div class="field mb-0"><input type="text" class="fb-text" data-id="${s.id}" placeholder="اكتب فيدباك..." value="${escapeHtml(s.feedback_text || "")}"></div>
              <div class="field mb-0" style="display:flex;gap:8px">
                <select class="fb-rating" data-id="${s.id}" style="width:auto"><option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option></select>
                <button class="btn btn-primary btn-sm fb-save" data-id="${s.id}">حفظ</button>
              </div>
            </div>
          </div>
        </div>`
      )
      .join("");
    document.querySelectorAll(".fb-save").forEach(
      (b) =>
        (b.onclick = async () => {
          const id = b.dataset.id;
          const text = document.querySelector(`.fb-text[data-id="${id}"]`).value.trim();
          const rating = Number(document.querySelector(`.fb-rating[data-id="${id}"]`).value);
          if (!text) return toast("اكتب فيدباك الأول", "error");
          try {
            await api.post(NS, "/teacher/homework/feedback", { submission_id: Number(id), feedback_text: text, rating });
            toast("تم حفظ الفيدباك بنجاح");
            openHomeworkSubmissionsModal(homework);
          } catch (err) {
            toast(err.message || "فشلت العملية", "error");
          }
        })
    );
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Consultation Slots ================= */
async function renderConsultationsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>مواعيد الاستشارة المتاحة</h3>
        <button class="btn btn-primary btn-sm" id="addSlotBtn">+ إضافة ميعاد</button>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>التاريخ</th><th>الوقت</th><th>نوع اللقاء</th><th>المادة</th><th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody id="csTbody"><tr class="loading-row"><td colspan="6"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("addSlotBtn").onclick = () => openConsultationSlotForm();
  await loadConsultationSlots();
}

const MEETING_TYPE_LABELS = { offline: "حضوري", video_call: "مكالمة فيديو", chat: "شات", online: "أونلاين" };

async function loadConsultationSlots() {
  const tbody = document.getElementById("csTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/teacher/consultations" + qs({ limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">لا توجد مواعيد بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (s) => {
          let statusBadge = `<span class="badge badge-success">متاح</span>`;
          if (s.status === 'approved') {
            statusBadge = `<span class="badge badge-success">مقبول — ${escapeHtml(s.student?.name || "")}</span>`;
          } else if (s.status === 'rejected') {
            statusBadge = `<span class="badge badge-danger">مرفوض — ${escapeHtml(s.student?.name || "")}</span>`;
          } else if (s.is_booked || s.status === 'pending') {
            statusBadge = `<span class="badge badge-warning">في الانتظار — ${escapeHtml(s.student?.name || "")}</span>`;
          }

          let actionsHtml = `<button class="btn btn-danger btn-sm" data-act="del" data-id="${s.id}">حذف</button>`;
          if (s.student_id && s.status === 'pending') {
            actionsHtml = `
              <button class="btn btn-success btn-sm" data-act="approve" data-id="${s.id}" style="margin-left:4px">قبول</button>
              <button class="btn btn-outline-danger btn-sm" data-act="reject" data-id="${s.id}">رفض</button>
            `;
          } else if (s.student_id && s.status === 'approved') {
            actionsHtml = `<button class="btn btn-outline-danger btn-sm" data-act="reject" data-id="${s.id}">تغيير لـ رفض</button>`;
          } else if (s.student_id && s.status === 'rejected') {
            actionsHtml = `<button class="btn btn-success btn-sm" data-act="approve" data-id="${s.id}">تغيير لـ قبول</button>`;
          }

          return `<tr>
            <td>${s.slot_date}</td>
            <td>${s.slot_time}</td>
            <td>${MEETING_TYPE_LABELS[s.meeting_type] || s.meeting_type}</td>
            <td>${escapeHtml(translatedName(s.subject, "ar") || "-")}</td>
            <td>${statusBadge}</td>
            <td class="row-actions">${actionsHtml}</td>
          </tr>`;
        }
      )
      .join("");

    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذا الميعاد؟", async () => {
            await api.del(NS, "/teacher/consultations", { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadConsultationSlots();
          }))
    );

    tbody.querySelectorAll("[data-act=approve]").forEach(
      (b) =>
        (b.onclick = async () => {
          await api.post(NS, "/teacher/consultations/respond", { slot_id: Number(b.dataset.id), action: "approve" });
          toast("تم قبول حجز الاستشارة بنجاح");
          loadConsultationSlots();
        })
    );

    tbody.querySelectorAll("[data-act=reject]").forEach(
      (b) =>
        (b.onclick = async () => {
          await api.post(NS, "/teacher/consultations/respond", { slot_id: Number(b.dataset.id), action: "reject" });
          toast("تم رفض حجز الاستشارة");
          loadConsultationSlots();
        })
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

function openConsultationSlotForm() {
  Modal.open({
    title: "إضافة ميعاد استشارة",
    bodyHtml: `
      <div class="field"><label>نوع اللقاء *</label>
        <select id="cs_meeting_type">
          <option value="video_call">مكالمة فيديو</option>
          <option value="offline">حضوري</option>
          <option value="chat">شات</option>
          <option value="online">أونلاين</option>
        </select>
      </div>
      <div class="field"><label>المادة (اختياري)</label><select id="cs_subject_id">${mySubjectsOptionsHtml()}</select></div>
      <div class="grid-2">
        <div class="field"><label>التاريخ *</label><input type="date" id="cs_date" required></div>
        <div class="field"><label>الوقت *</label><input type="time" id="cs_time" required></div>
      </div>
      <div class="field"><label>ملاحظات</label><textarea id="cs_notes" rows="2"></textarea></div>`,
    footerHtml: `<button class="btn btn-outline" id="csCancel">إلغاء</button><button class="btn btn-primary" id="csSave">إضافة</button>`,
  });
  document.getElementById("csCancel").onclick = () => Modal.close();
  document.getElementById("csSave").onclick = async () => {
    const btn = document.getElementById("csSave");
    const slotDate = document.getElementById("cs_date").value;
    const slotTime = document.getElementById("cs_time").value;
    if (!slotDate || !slotTime) return toast("حدد التاريخ والوقت", "error");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/teacher/consultations", {
        meeting_type: document.getElementById("cs_meeting_type").value,
        subject_id: Number(document.getElementById("cs_subject_id").value) || undefined,
        slot_date: slotDate,
        slot_time: slotTime,
        notes: document.getElementById("cs_notes").value.trim(),
      });
      toast("تمت إضافة الميعاد بنجاح");
      Modal.close();
      loadConsultationSlots();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "إضافة";
    }
  };
}

/* ================= Class Sessions + Reschedule Requests ================= */
async function renderSessionsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>جدول الحصص الأسبوعي</h3>
        <div class="toolbar">
          <button class="btn btn-outline btn-sm" id="incomingReqBtn">📥 طلبات تغيير من الطلاب</button>
          <button class="btn btn-outline btn-sm" id="myRequestsBtn">📋 طلبات التغيير بتاعتي</button>
          <button class="btn btn-primary btn-sm" id="addSessionBtn">+ إضافة حصة</button>
        </div>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الكود</th><th>اليوم</th><th>الوقت</th><th>القاعة</th><th>الصف</th><th>النطاق</th><th>إجراءات</th></tr></thead>
          <tbody id="sesTbody"><tr class="loading-row"><td colspan="7"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("addSessionBtn").onclick = () => openSessionForm(null);
  document.getElementById("myRequestsBtn").onclick = () => openMyRescheduleRequestsModal();
  document.getElementById("incomingReqBtn").onclick = () => openIncomingRescheduleRequestsModal();
  await loadSessionsList();
}

const INCOMING_REQ_STATUS_BADGES = { pending: `<span class="badge badge-warning">معلّقة</span>`, approved: `<span class="badge badge-success">تمت الموافقة</span>`, rejected: `<span class="badge badge-danger">مرفوضة</span>` };
async function openIncomingRescheduleRequestsModal() {
  Modal.open({ title: "طلبات تغيير المواعيد من الطلاب", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" id="irqClose">إغلاق</button>` });
  document.getElementById("irqClose").onclick = () => Modal.close();
  await loadIncomingRescheduleRequests();
}
async function loadIncomingRescheduleRequests() {
  try {
    const res = await api.get(NS, "/teacher/sessions/reschedule-requests/incoming");
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      document.getElementById("modalBody").innerHTML = `<div class="empty-state">لا توجد طلبات من الطلاب</div>`;
      return;
    }
    document.getElementById("modalBody").innerHTML = list
      .map(
        (r) => `<div class="card" style="margin-bottom:10px">
          <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
              <div><b>${escapeHtml(r.requester?.name || "طالب")}</b> <span class="text-muted">— ${escapeHtml(r.requester?.email || "")}</span></div>
              ${INCOMING_REQ_STATUS_BADGES[r.status] || escapeHtml(r.status)}
            </div>
            <div class="text-muted" style="font-size:13px;margin-top:6px">الحصة: ${escapeHtml(r.session?.code || "-")} — مقترح: ${DAY_LABELS_AR[r.new_day] || r.new_day} (${r.new_start_time || ""}-${r.new_end_time || ""})</div>
            <div style="margin-top:6px">${escapeHtml(r.reason)}</div>
            ${
              r.status === "pending"
                ? `<div class="row-actions" style="margin-top:10px">
                    <button class="btn btn-primary btn-sm" data-act="approve" data-id="${r.id}">✅ موافقة</button>
                    <button class="btn btn-danger btn-sm" data-act="reject" data-id="${r.id}">❌ رفض</button>
                  </div>`
                : ""
            }
          </div>
        </div>`
      )
      .join("");
    document.querySelectorAll("[data-act=approve]").forEach((b) => (b.onclick = () => reviewIncomingRescheduleRequest(Number(b.dataset.id), "approved")));
    document.querySelectorAll("[data-act=reject]").forEach((b) => (b.onclick = () => reviewIncomingRescheduleRequest(Number(b.dataset.id), "rejected")));
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}
async function reviewIncomingRescheduleRequest(id, decision) {
  try {
    await api.put(NS, "/teacher/sessions/reschedule-requests/review", { id, decision });
    toast(decision === "approved" ? "تمت الموافقة على الطلب" : "تم رفض الطلب");
    loadIncomingRescheduleRequests();
    loadSessionsList();
  } catch (err) {
    toast(err.message || "فشلت العملية", "error");
  }
}

async function loadSessionsList() {
  const tbody = document.getElementById("sesTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/teacher/sessions");
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">لا توجد حصص بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = list
      .map(
        (s) => `<tr>
          <td>${escapeHtml(s.code || "-")}</td>
          <td>${escapeHtml(s.day_of_week)}</td>
          <td>${s.start_time}-${s.end_time} ${s.is_available_now ? `<span class="badge badge-success">شغالة دلوقتي</span>` : ""}</td>
          <td>${s.is_online ? `<a href="${escapeHtml(s.meeting_link)}" target="_blank" rel="noopener" class="chip">🔗 رابط أونلاين</a>` : escapeHtml(s.room || "-")}</td>
          <td>${escapeHtml(s.grade || "-")}</td>
          <td>${s.scope === "course" ? `📚 كورس (${s.students_count} طالب)` : `📘 مادة كاملة (${s.students_count} طالب)`} ${s.is_active ? "" : `<span class="badge badge-danger">منتهية</span>`}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${s.id}">تعديل</button>
            <button class="btn btn-outline btn-sm" data-act="attendance" data-id="${s.id}">📋 الحضور</button>
            <button class="btn btn-outline btn-sm" data-act="reschedule" data-id="${s.id}">📅 طلب تغيير</button>
            ${s.is_active ? `<button class="btn btn-outline btn-sm" data-act="end" data-id="${s.id}">🛑 إنهاء الحصة</button>` : ""}
            <button class="btn btn-danger btn-sm" data-act="del" data-id="${s.id}">حذف</button>
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openSessionForm(list.find((i) => String(i.id) === b.dataset.id))));
    tbody.querySelectorAll("[data-act=attendance]").forEach((b) => (b.onclick = () => openAttendanceModal(list.find((i) => String(i.id) === b.dataset.id))));
    tbody.querySelectorAll("[data-act=reschedule]").forEach((b) => (b.onclick = () => openRescheduleRequestForm(list.find((i) => String(i.id) === b.dataset.id))));
    tbody.querySelectorAll("[data-act=end]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد إنهاء هذه الحصة نهائيًا؟ مش هتقدر ترجعها تاني غير بالتعديل اليدوي.", async () => {
            await api.put(NS, "/teacher/sessions", { id: Number(b.dataset.id), is_active: 0 });
            toast("تم إنهاء الحصة");
            loadSessionsList();
          }))
    );
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذه الحصة؟", async () => {
            await api.del(NS, "/teacher/sessions", { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadSessionsList();
          }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

const DAY_OPTIONS = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
const DAY_LABELS_AR = { Sunday: "الأحد", Monday: "الإثنين", Tuesday: "الثلاثاء", Wednesday: "الأربعاء", Thursday: "الخميس", Friday: "الجمعة", Saturday: "السبت" };

function openSessionForm(row) {
  const isEdit = !!row;
  Modal.open({
    title: isEdit ? "تعديل حصة" : "إضافة حصة جديدة",
    bodyHtml: `
      <p class="text-muted" style="font-size:12.5px">حدد إما مادة كاملة (تظهر لكل المشتركين فيها) أو كورس معين (يظهر لمشتركي الكورس بس).</p>
      <div class="field"><label>مادة كاملة</label><select id="sf_subject_id">${mySubjectsOptionsHtml(row?.subject_id)}</select></div>
      ${asyncSelectHtml("sf_course", { label: "أو كورس معين", currentId: row?.course_id ?? "", currentLabel: row?.course ? (row.course.name_ar || translatedName(row.course, "ar")) : "" })}
      <div class="grid-2">
        <div class="field"><label>كود الحصة</label><input type="text" id="sf_code" value="${escapeHtml(row?.code || "")}" placeholder="MATH-301"></div>
        <div class="field"><label>اليوم *</label><select id="sf_day">${DAY_OPTIONS.map((d) => `<option value="${d}" ${row?.day_of_week === d ? "selected" : ""}>${DAY_LABELS_AR[d]}</option>`).join("")}</select></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>من الساعة *</label><input type="time" id="sf_start" value="${row?.start_time?.slice(0, 5) || ""}"></div>
        <div class="field"><label>إلى الساعة *</label><input type="time" id="sf_end" value="${row?.end_time?.slice(0, 5) || ""}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>القاعة</label><input type="text" id="sf_room" value="${escapeHtml(row?.room || "")}" placeholder="مثال: قاعة 3"></div>
        <div class="field"><label>الصف</label><input type="text" id="sf_grade" value="${escapeHtml(row?.grade || "")}"></div>
      </div>
      <div class="field" style="display:flex;align-items:center;gap:8px">
        <input type="checkbox" id="sf_is_online" ${row?.is_online ? "checked" : ""} style="width:18px;height:18px">
        <label style="margin-bottom:0" for="sf_is_online">حصة أونلاين (عن بعد)؟</label>
      </div>
      <div class="field" id="sf_link_wrap" style="display:${row?.is_online ? "block" : "none"}">
        <label>رابط الحصة *</label>
        <input type="url" id="sf_meeting_link" value="${escapeHtml(row?.meeting_link || "")}" placeholder="https://meet.google.com/...">
      </div>
      <div class="field"><label>مفعّلة؟</label><select id="sf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`,
    footerHtml: `<button class="btn btn-outline" id="sfCancel">إلغاء</button><button class="btn btn-primary" id="sfSave">${isEdit ? "حفظ التعديلات" : "إضافة"}</button>`,
  });
  wireAsyncSelect("sf_course", fetchTeacherCourses);
  document.getElementById("sf_is_online").onchange = (e) => {
    document.getElementById("sf_link_wrap").style.display = e.target.checked ? "block" : "none";
  };
  document.getElementById("sfCancel").onclick = () => Modal.close();
  document.getElementById("sfSave").onclick = async () => {
    const btn = document.getElementById("sfSave");
    const courseId = readAsyncSelect("sf_course");
    const subjectId = Number(document.getElementById("sf_subject_id").value) || undefined;
    if (!courseId && !subjectId) return toast("حدد مادة أو كورس", "error");
    const isOnline = document.getElementById("sf_is_online").checked ? 1 : 0;
    const meetingLink = document.getElementById("sf_meeting_link").value.trim();
    if (isOnline && !meetingLink) return toast("اكتب رابط الحصة الأونلاين", "error");
    const payload = {
      subject_id: courseId ? undefined : subjectId,
      course_id: courseId || undefined,
      code: document.getElementById("sf_code").value.trim(),
      day_of_week: document.getElementById("sf_day").value,
      start_time: document.getElementById("sf_start").value,
      end_time: document.getElementById("sf_end").value,
      room: document.getElementById("sf_room").value.trim(),
      is_online: isOnline,
      meeting_link: isOnline ? meetingLink : "",
      grade: document.getElementById("sf_grade").value.trim(),
      is_active: Number(document.getElementById("sf_is_active").value),
    };
    if (!payload.start_time || !payload.end_time) return toast("حدد وقت البداية والنهاية", "error");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, "/teacher/sessions", payload);
      } else {
        await api.post(NS, "/teacher/sessions", payload);
      }
      toast(isEdit ? "تم التحديث" : "تمت الإضافة بنجاح");
      Modal.close();
      loadSessionsList();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ التعديلات" : "إضافة";
    }
  };
}

function openRescheduleRequestForm(session) {
  Modal.open({
    title: "طلب تغيير ميعاد الحصة",
    bodyHtml: `
      <div class="field">
        <label>الحصة الحالية</label>
        <div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${escapeHtml(session.code || "-")} — ${DAY_LABELS_AR[session.day_of_week] || session.day_of_week} (${session.start_time}-${session.end_time})</div>
      </div>
      <div class="field"><label>اليوم الجديد *</label><select id="rq_day">${DAY_OPTIONS.map((d) => `<option value="${d}">${DAY_LABELS_AR[d]}</option>`).join("")}</select></div>
      <div class="grid-2">
        <div class="field"><label>من الساعة</label><input type="time" id="rq_start" value="${session.start_time?.slice(0, 5) || ""}"></div>
        <div class="field"><label>إلى الساعة</label><input type="time" id="rq_end" value="${session.end_time?.slice(0, 5) || ""}"></div>
      </div>
      <div class="field"><label>سبب الطلب *</label><textarea id="rq_reason" rows="2" required></textarea></div>`,
    footerHtml: `<button class="btn btn-outline" id="rqCancel">إلغاء</button><button class="btn btn-primary" id="rqSend">إرسال للأدمن</button>`,
  });
  document.getElementById("rqCancel").onclick = () => Modal.close();
  document.getElementById("rqSend").onclick = async () => {
    const btn = document.getElementById("rqSend");
    const reason = document.getElementById("rq_reason").value.trim();
    if (!reason) return toast("اكتب سبب الطلب", "error");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/teacher/sessions/reschedule-request", {
        session_id: session.id,
        new_day: document.getElementById("rq_day").value,
        new_start_time: document.getElementById("rq_start").value,
        new_end_time: document.getElementById("rq_end").value,
        reason,
      });
      toast("تم إرسال الطلب للأدمن بنجاح");
      Modal.close();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "إرسال للأدمن";
    }
  };
}

/* ================= Attendance ================= */
function openAttendanceModal(session) {
  const today = new Date().toISOString().slice(0, 10);
  Modal.open({
    title: `الحضور: ${escapeHtml(session.code || "")}`,
    size: "modal-lg",
    bodyHtml: `
      <div class="toolbar" style="margin-bottom:14px;justify-content:space-between">
        <div class="field mb-0" style="width:auto"><input type="date" id="attDate" value="${today}"></div>
        <button class="btn btn-outline btn-sm" id="attStatsBtn">📊 إحصائية الحضور</button>
      </div>
      <div id="attRoster"><div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div></div>`,
    footerHtml: `<button class="btn btn-outline" id="attCancel">إغلاق</button><button class="btn btn-primary" id="attSave">حفظ الحضور</button>`,
  });
  document.getElementById("attCancel").onclick = () => Modal.close();
  document.getElementById("attDate").onchange = () => loadAttendanceRoster(session.id);
  document.getElementById("attStatsBtn").onclick = () => openAttendanceStatsModal(session);
  document.getElementById("attSave").onclick = async () => {
    const btn = document.getElementById("attSave");
    const presentIds = Array.from(document.querySelectorAll("#attRoster input[type=checkbox]:checked")).map((c) => Number(c.value));
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/teacher/sessions/attendance/mark", { session_id: session.id, date: document.getElementById("attDate").value, present_student_ids: presentIds });
      toast("تم حفظ الحضور بنجاح");
      Modal.close();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
    } finally {
      btn.disabled = false;
      btn.textContent = "حفظ الحضور";
    }
  };
  loadAttendanceRoster(session.id);
}

async function loadAttendanceRoster(sessionId) {
  const box = document.getElementById("attRoster");
  if (!box) return;
  box.innerHTML = `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`;
  try {
    const date = document.getElementById("attDate").value;
    const res = await api.get(NS, "/teacher/sessions/attendance/roster" + qs({ session_id: sessionId, date }));
    const d = res.data || res;
    const students = d.students || [];
    if (!students.length) {
      box.innerHTML = `<div class="empty-state">لا يوجد طلاب مشتركون في هذه الحصة</div>`;
      return;
    }
    box.innerHTML = `<div class="multiselect-box" style="grid-template-columns:1fr 1fr">
      ${students
        .map(
          (s) => `<label>
            <input type="checkbox" value="${s.id}" ${s.is_present ? "checked" : ""}>
            ${escapeHtml(s.name)} <span class="text-muted" style="font-size:11.5px">(${escapeHtml(s.email)})</span>
          </label>`
        )
        .join("")}
    </div>`;
  } catch (err) {
    box.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

async function openAttendanceStatsModal(session) {
  Modal.open({ title: `إحصائية حضور: ${escapeHtml(session.code || "")}`, size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" id="asClose">إغلاق</button>` });
  document.getElementById("asClose").onclick = () => Modal.close();
  try {
    const res = await api.get(NS, "/teacher/sessions/attendance/stats" + qs({ session_id: session.id }));
    const d = res.data || res;
    const students = d.students || [];
    document.getElementById("modalBody").innerHTML = `
      <div class="stats-bar">
        <div class="stat-chip stat-chip-primary"><span class="stat-chip-icon">📅</span><span class="stat-chip-text"><span class="stat-chip-value">${d.total_occurrences_marked ?? 0}</span><span class="stat-chip-label">مرات تم تسجيل الحضور فيها</span></span></div>
        <div class="stat-chip stat-chip-muted"><span class="stat-chip-icon">👥</span><span class="stat-chip-text"><span class="stat-chip-value">${d.expected_students_count ?? 0}</span><span class="stat-chip-label">الطلاب المتوقعون</span></span></div>
      </div>
      <div class="table-scroll"><table>
        <thead><tr><th>الطالب</th><th>عدد مرات الحضور</th><th>نسبة الحضور</th></tr></thead>
        <tbody>${
          students.length
            ? students
                .map(
                  (s) => `<tr>
                    <td>${escapeHtml(s.name)} <span class="text-muted" style="font-size:11.5px">(${escapeHtml(s.email)})</span></td>
                    <td>${s.present_count}</td>
                    <td><div class="progress-bar"><div class="progress-bar-fill" style="width:${s.attendance_rate}%"></div></div> ${s.attendance_rate}%</td>
                  </tr>`
                )
                .join("")
            : `<tr><td colspan="3"><div class="empty-state">لا يوجد طلاب</div></td></tr>`
        }</tbody>
      </table></div>`;
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

async function openMyRescheduleRequestsModal() {
  Modal.open({ title: "طلبات تغيير المواعيد بتاعتي", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: `<button class="btn btn-outline" id="mrClose">إغلاق</button>` });
  document.getElementById("mrClose").onclick = () => Modal.close();
  const statusBadges = { pending: `<span class="badge badge-warning">معلّقة</span>`, approved: `<span class="badge badge-success">تمت الموافقة</span>`, rejected: `<span class="badge badge-danger">مرفوضة</span>` };
  try {
    const res = await api.get(NS, "/teacher/sessions/reschedule-requests");
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      document.getElementById("modalBody").innerHTML = `<div class="empty-state">لا توجد طلبات</div>`;
      return;
    }
    document.getElementById("modalBody").innerHTML = `
      <div class="table-scroll"><table>
        <thead><tr><th>الحصة</th><th>اليوم الجديد</th><th>السبب</th><th>الحالة</th></tr></thead>
        <tbody>
          ${list
            .map(
              (r) => `<tr>
                <td>${escapeHtml(r.session?.code || "-")}</td>
                <td>${DAY_LABELS_AR[r.new_day] || r.new_day} (${r.new_start_time || ""}-${r.new_end_time || ""})</td>
                <td style="max-width:200px;white-space:normal">${escapeHtml(r.reason)}</td>
                <td>${statusBadges[r.status] || escapeHtml(r.status)}</td>
              </tr>`
            )
            .join("")}
        </tbody>
      </table></div>`;
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ================= Exam Attempts (Grading) ================= */
function showTExamAttemptsPage(exam) {
  currentView = "exam-attempts";
  customPageRefresh = () => renderTExamAttemptsPage(document.getElementById("viewRoot"), exam);
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
  document.getElementById("pageTitle").textContent = `نتائج الامتحان: ${exam.title_ar || exam.title}`;
  renderTExamAttemptsPage(document.getElementById("viewRoot"), exam);
}

const T_ATTEMPT_STATUS_BADGES = {
  in_progress: `<span class="badge badge-muted">جاري الحل</span>`,
  submitted: `<span class="badge badge-warning">بانتظار التصحيح</span>`,
  graded: `<span class="badge badge-success">تم التصحيح</span>`,
};

function renderTExamAttemptsPage(root, exam) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" id="backToExams">→ رجوع للامتحانات</button>
          <h3 class="mb-0">نتائج الامتحان: ${escapeHtml(exam.title_ar || exam.title)}</h3>
        </div>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الطالب</th><th>الحالة</th><th>الدرجة</th><th>وقت التسليم</th><th>إجراءات</th></tr></thead>
          <tbody id="teaTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("backToExams").onclick = () => navigate("exams");
  loadTExamAttempts(exam);
}

async function loadTExamAttempts(exam) {
  const tbody = document.getElementById("teaTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/teacher/exams/attempts" + qs({ exam_id: exam.id }));
    const items = (res.data || res) || [];
    const list = Array.isArray(items) ? items : [];
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد محاولات لهذا الامتحان بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = list
      .map(
        (a) => `<tr>
          <td>${escapeHtml(a.student?.name || "-")} <span class="text-muted">— ${escapeHtml(a.student?.email || "")}</span></td>
          <td>${T_ATTEMPT_STATUS_BADGES[a.status] || escapeHtml(a.status)}</td>
          <td>${a.total_score ?? "-"} / ${a.max_score}</td>
          <td>${a.submitted_at ? fmtDate(a.submitted_at) : "-"}</td>
          <td class="row-actions"><button class="btn btn-outline btn-sm" data-act="grade" data-id="${a.id}">${a.status === "submitted" ? "تصحيح" : "عرض"}</button></td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=grade]").forEach((b) => (b.onclick = () => openTGradeAttemptModal(Number(b.dataset.id), exam)));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

async function openTGradeAttemptModal(attemptId, exam) {
  Modal.open({ title: "تفاصيل المحاولة", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: "" });
  try {
    const res = await api.get(NS, "/teacher/exams/attempts/show" + qs({ id: attemptId }));
    const attempt = res.data || res;
    const answers = attempt.answers || [];
    document.getElementById("modalBody").innerHTML = `
      <div class="field">
        <label>الطالب</label>
        <div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${escapeHtml(attempt.student?.name || "-")} — ${escapeHtml(attempt.student?.email || "")}</div>
      </div>
      <div class="field"><label>الدرجة الكلية</label><div>${attempt.total_score ?? "-"} / ${attempt.max_score}</div></div>
      ${answers
        .map(
          (ans, idx) => `
        <div class="card" style="margin-bottom:10px">
          <div class="card-body">
            <div style="font-weight:700;margin-bottom:6px">${idx + 1}. ${escapeHtml(ans.question_text || "")}</div>
            ${
              ans.question_type === "mcq"
                ? `<div class="text-muted" style="font-size:13px;margin-bottom:6px">${(ans.options || []).map((o) => `${o.is_correct ? "✓" : "—"} ${escapeHtml(o.option_text || "")}${o.id === ans.mcq_option_id ? " (إجابة الطالب)" : ""}`).join("<br>")}</div>`
                : ans.question_type === "true_false"
                ? `<div class="text-muted" style="font-size:13px;margin-bottom:6px">إجابة الطالب: ${ans.true_false_answer ? "صح" : "خطأ"} — الصح: ${ans.correct_answer ? "صح" : "خطأ"}</div>`
                : `<div class="text-muted" style="font-size:13px;margin-bottom:6px">إجابة الطالب: ${escapeHtml(ans.written_answer_text || "-")}</div>`
            }
            <div class="grid-2">
              <div class="field mb-0"><label>الدرجة (من ${ans.max_question_score})</label><input type="number" step="0.5" class="ga-score" data-question-id="${ans.question_id}" value="${ans.score_awarded ?? ""}"></div>
              <div class="field mb-0"><label>ملاحظة</label><input type="text" class="ga-note" data-question-id="${ans.question_id}" value="${escapeHtml(ans.teacher_note || "")}"></div>
            </div>
          </div>
        </div>`
        )
        .join("")}`;
    document.getElementById("modalFooter").innerHTML = `<button class="btn btn-outline" id="tgaCancel">إغلاق</button><button class="btn btn-primary" id="tgaSave">حفظ الدرجات</button>`;
    document.getElementById("tgaCancel").onclick = () => Modal.close();
    document.getElementById("tgaSave").onclick = async () => {
      const btn = document.getElementById("tgaSave");
      const gradeAnswers = Array.from(document.querySelectorAll(".ga-score"))
        .map((input) => {
          const qId = Number(input.dataset.questionId);
          const noteEl = document.querySelector(`.ga-note[data-question-id="${qId}"]`);
          return { question_id: qId, score_awarded: input.value === "" ? null : Number(input.value), teacher_note: noteEl?.value.trim() || undefined };
        })
        .filter((a) => a.score_awarded !== null);
      try {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner"></span>`;
        await api.put(NS, "/teacher/exams/attempts", { attempt_id: attemptId, answers: gradeAnswers });
        toast("تم حفظ الدرجات بنجاح");
        Modal.close();
        loadTExamAttempts(exam);
      } catch (err) {
        toast(err.message || "فشلت العملية", "error");
        btn.disabled = false;
        btn.textContent = "حفظ الدرجات";
      }
    };
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ---- boot ---- */
(function init() {
  if (isLoggedIn()) showApp();
  else showAuth();
})();
