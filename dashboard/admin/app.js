/* ==== Najeh Admin Dashboard ==== */
const NS = "admin";

/* ---------- Response shape normalizer ---------- */
function extractList(res) {
  const d = res.data ?? res;
  let items = [];
  let page = 1, totalPages = 1, total = null;
  if (Array.isArray(d)) items = d;
  else if (Array.isArray(d.items)) items = d.items;
  else if (Array.isArray(d.data)) items = d.data;
  else if (Array.isArray(d.list)) items = d.list;

  const meta = res.pagination || res.meta || d.meta || d.pagination || {};
  page = Number(meta.page || meta.current_page || d.page || 1);
  totalPages = Number(meta.total_pages || meta.last_page || meta.pages || d.total_pages || 1);
  total = meta.total ?? meta.total_items ?? meta.total_records ?? d.total ?? items.length;
  const statistics = res.statistics || d.statistics || null;
  return { items, page, totalPages, total, statistics };
}

/* ---------- Async-select entity fetchers (city_id / school_id / school_type_id / role_id, ...) ---------- */
function makeEntityFetcher(path, labelFn) {
  return async (page, search) => {
    const res = await api.get(NS, path + qs({ page, limit: 20, search }));
    const { items, page: p, totalPages } = extractList(res);
    return { items: items.map((r) => ({ id: r.id, label: labelFn(r) })), hasMore: p < totalPages };
  };
}
function makeEntityResolver(showPath, labelFn) {
  return async (id) => {
    const res = await api.get(NS, showPath + qs({ id }));
    const row = res.data || res;
    return row ? labelFn(row) : null;
  };
}
const cityLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;
const schoolLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;
const schoolTypeLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;
const roleLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;

const courseCategoryLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;
const academicYearLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;
const subjectLabel = (r) => translatedName(r, "ar") || r.name_ar || r.name || `#${r.id}`;

const fetchCities = makeEntityFetcher("/admin/cities", cityLabel);
const fetchSchools = makeEntityFetcher("/admin/schools", schoolLabel);
const fetchSchoolTypes = makeEntityFetcher("/admin/school-types", schoolTypeLabel);
const fetchRoles = makeEntityFetcher("/admin/roles", roleLabel);
const fetchCourseCategories = makeEntityFetcher("/admin/course-categories", courseCategoryLabel);
const fetchAcademicYears = makeEntityFetcher("/admin/academic-years", academicYearLabel);
const fetchAdminSubjects = makeEntityFetcher("/admin/subjects", subjectLabel);
const resolveCityLabel = makeEntityResolver("/admin/cities/show", cityLabel);
const resolveSchoolLabel = makeEntityResolver("/admin/schools/show", schoolLabel);
const resolveSchoolTypeLabel = makeEntityResolver("/admin/school-types/show", schoolTypeLabel);
const resolveCourseCategoryLabel = makeEntityResolver("/admin/course-categories/show", courseCategoryLabel);
const resolveAcademicYearLabel = makeEntityResolver("/admin/academic-years/show", academicYearLabel);
const resolveSubjectLabel = makeEntityResolver("/admin/subjects/show", subjectLabel);

const unitLabel = (r) => r.name_ar || translatedName(r, "ar") || r.code || `#${r.id}`;
const courseLabel = (r) => r.name_ar || translatedName(r, "ar") || `#${r.id}`;
const fetchUnits = makeEntityFetcher("/admin/units", unitLabel);
const fetchAdminCourses = makeEntityFetcher("/admin/courses", courseLabel);
const resolveUnitLabel = makeEntityResolver("/admin/units/show", unitLabel);
const resolveCourseLabel = makeEntityResolver("/admin/courses/show", courseLabel);

const examLabel = (r) => r.title_ar || r.title || `#${r.id}`;
const fetchAdminExams = makeEntityFetcher("/admin/exams", examLabel);
const resolveExamLabel = makeEntityResolver("/admin/exams/show", examLabel);

/* small caches for multiselects (checkbox lists, not infinite-scroll) */
let academicYearsCache = null;
async function getAcademicYearsCache() {
  if (academicYearsCache) return academicYearsCache;
  try {
    const res = await api.get(NS, "/admin/academic-years" + qs({ limit: 100 }));
    academicYearsCache = extractList(res).items.map((y) => ({ id: y.id, label: translatedName(y, "ar") }));
  } catch (e) {
    academicYearsCache = [];
  }
  return academicYearsCache;
}
let teachersCache = null;
async function getTeachersCache() {
  if (teachersCache) return teachersCache;
  try {
    const res = await api.get(NS, "/admin/teachers" + qs({ limit: 100 }));
    teachersCache = extractList(res).items.map((t) => ({ id: t.id, label: t.name }));
  } catch (e) {
    teachersCache = [];
  }
  return teachersCache;
}
let subjectsCacheFlat = null;
async function getSubjectsCacheFlat() {
  if (subjectsCacheFlat) return subjectsCacheFlat;
  try {
    const res = await api.get(NS, "/admin/subjects" + qs({ limit: 100 }));
    subjectsCacheFlat = extractList(res).items.map((s) => ({ id: s.id, label: subjectLabel(s) }));
  } catch (e) {
    subjectsCacheFlat = [];
  }
  return subjectsCacheFlat;
}
let coursesCacheFlat = null;
async function getCoursesCacheFlat() {
  if (coursesCacheFlat) return coursesCacheFlat;
  try {
    const res = await api.get(NS, "/admin/courses" + qs({ limit: 100 }));
    coursesCacheFlat = extractList(res).items.map((c) => ({ id: c.id, label: courseLabel(c) }));
  } catch (e) {
    coursesCacheFlat = [];
  }
  return coursesCacheFlat;
}
const fetchAdminStudents = makeEntityFetcher("/admin/students", (r) => `${r.name} — ${r.email}`);
const fetchAdminTeachers = makeEntityFetcher("/admin/teachers", (r) => r.name);

/* ---------- Auth ---------- */
function isLoggedIn() {
  return !!getTokens(NS).token;
}

async function doLogin(email, password) {
  const res = await api.post(NS, "/admin/auth/login", { email, password }, false);
  const d = res.data || res;
  const tokens = d.tokens || d;
  setTokens(NS, { token: tokens.access_token || tokens.token, refresh_token: tokens.refresh_token });
  Store.set("admin_user", d.user || d.admin || d);
  return d;
}

function doLogout() {
  clearTokens(NS);
  location.reload();
}

window.addEventListener("najeh:unauthorized", () => {
  toast("انتهت الجلسة، سجل الدخول مرة أخرى", "error");
  showLogin();
});

/* ---------- View switching ---------- */
function showLogin() {
  document.getElementById("loginView").style.display = "flex";
  document.getElementById("appShell").style.display = "none";
}
function showApp() {
  document.getElementById("loginView").style.display = "none";
  document.getElementById("appShell").style.display = "flex";
  const user = Store.get("admin_user") || {};
  document.getElementById("sideUserName").textContent = user.name || "مدير النظام";
  document.getElementById("sideUserRole").textContent = user.role || "Admin";
  if (user.image) document.getElementById("sideUserImg").src = user.image;
  navigate("dashboard");
}

document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = document.getElementById("loginBtn");
  const errBox = document.getElementById("loginError");
  errBox.classList.remove("show");
  btn.disabled = true;
  btn.innerHTML = `<span class="spinner"></span>`;
  try {
    await doLogin(document.getElementById("loginEmail").value.trim(), document.getElementById("loginPassword").value);
    showApp();
  } catch (err) {
    errBox.textContent = err.message || "فشل تسجيل الدخول";
    errBox.classList.add("show");
  } finally {
    btn.disabled = false;
    btn.textContent = "دخول";
  }
});

document.getElementById("logoutBtn").addEventListener("click", doLogout);

function toggleSidebar(open) {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  const willOpen = open ?? !sidebar.classList.contains("open");
  sidebar.classList.toggle("open", willOpen);
  overlay.classList.toggle("show", willOpen);
}
document.getElementById("menuToggle").addEventListener("click", () => toggleSidebar());
document.getElementById("sidebarOverlay").addEventListener("click", () => toggleSidebar(false));
function applyLangDirection(lang) {
  const dir = lang === "en" ? "ltr" : "rtl";
  document.documentElement.setAttribute("dir", dir);
  document.documentElement.setAttribute("lang", lang);
}
document.getElementById("langSwitch").addEventListener("change", (e) => {
  localStorage.setItem("najeh_lang", e.target.value);
  applyLangDirection(e.target.value);
  navigate(currentView);
});
document.getElementById("refreshBtn").addEventListener("click", () => (customPageRefresh ? customPageRefresh() : navigate(currentView, true)));

/** Set by drill-down pages (course units / unit content) that aren't part of the sidebar's nav map,
 * so the topbar refresh button knows how to re-render them instead of falling through to navigate(). */
let customPageRefresh = null;

const titles = {
  dashboard: "الإحصائيات",
  students: "إدارة الطلاب",
  teachers: "إدارة المعلمين",
  parents: "إدارة أولياء الأمور",
  admins: "إدارة المسؤولين",
  "student-reports": "التقارير الأكاديمية",
  roles: "الرولز والصلاحيات",
  courses: "إدارة الكورسات",
  units: "الوحدات الدراسية",
  videos: "الفيديوهات",
  books: "الكتب",
  exams: "الامتحانات",
  banners: "البانرات الإعلانية",
  certificates: "الشهادات",
  "subscription-codes": "أكواد الاشتراك",
  subscriptions: "اشتراكات الطلاب",
  "reschedule-requests": "طلبات تغيير المواعيد",
  lookups: "الإعدادات العامة",
  "site-settings": "التحكم بالموقع الرئيسي والصفحة الرئيسية",
};

let currentView = "dashboard";

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
  document.getElementById("pageTitle").textContent = titles[view] || "";
  const root = document.getElementById("viewRoot");
  root.innerHTML = `<div class="full-loader" style="min-height:200px"><span class="spinner spinner-dark" style="width:26px;height:26px"></span></div>`;
  const renderer = {
    dashboard: renderDashboard,
    students: () => renderCrudView(studentsCfg),
    teachers: () => renderCrudView(teachersCfg),
    parents: () => renderCrudView(parentsCfg),
    admins: () => renderCrudView(adminsCfg),
    "student-reports": () => renderCrudView(studentReportsCfg),
    roles: renderRolesView,
    courses: () => renderCrudView(coursesCfg),
    units: () => renderCrudView(unitsCfg),
    videos: () => renderCrudView(videosCfg),
    books: () => renderCrudView(booksCfg),
    exams: () => renderCrudView(examsCfg),
    banners: () => renderCrudView(bannersCfg),
    certificates: () => renderCrudView(certificatesCfg),
    "subscription-codes": () => renderCrudView(subscriptionCodesCfg),
    subscriptions: renderSubscriptionsView,
    "reschedule-requests": renderRescheduleRequestsView,
    lookups: renderLookupsView,
    "site-settings": renderSiteSettingsView,
  }[view];
  if (renderer) renderer(root);
}

/* ================= Dashboard ================= */
async function renderDashboard(root) {
  try {
    const res = await api.get(NS, "/admin/dashboard/statistics");
    const s = (res.data && res.data.statistics) || res.data || res;
    const cards = [
      { key: "students", label: "إجمالي الطلاب", icon: "🎓", color: "#4f5fe8", val: s.students?.total },
      { key: "students_verified", label: "طلاب مؤكدون بريديًا", icon: "✅", color: "#1fa96e", val: s.students?.email_verified },
      { key: "teachers", label: "إجمالي المعلمين", icon: "👨‍🏫", color: "#1fa96e", val: s.teachers?.total },
      { key: "pending_teachers", label: "معلمون بانتظار التفعيل", icon: "⏳", color: "#e5484d", val: s.teachers?.total - s.teachers?.active },
      { key: "parents", label: "أولياء الأمور", icon: "👨‍👩‍👦", color: "#d98a1f", val: s.parents?.total },
      { key: "admins", label: "المسؤولون", icon: "🛡️", color: "#7b5ce8", val: s.admins?.total },
      { key: "schools", label: "المدارس", icon: "🏫", color: "#4f5fe8", val: s.schools?.total },
      { key: "subjects", label: "المواد الدراسية", icon: "📚", color: "#7b5ce8", val: s.subjects?.total },
      { key: "cities", label: "المدن", icon: "🏙️", color: "#d98a1f", val: s.cities?.total },
    ];
    root.innerHTML = `
      <div class="stat-grid">
        ${cards
        .map(
          (c) => `<div class="stat-card">
              <div class="icon" style="background:${c.color}22;color:${c.color}">${c.icon}</div>
              <div class="value">${c.val ?? "—"}</div>
              <div class="label">${c.label}</div>
            </div>`
        )
        .join("")}
      </div>`;
  } catch (err) {
    root.innerHTML = `<div class="card"><div class="card-body error-box show">${escapeHtml(err.message)}</div></div>`;
  }
}

/* ================= Generic CRUD engine for people (students/teachers/parents/admins) ================= */
const crudState = {}; // per-entity: {page, search}

function ensureState(key) {
  if (!crudState[key]) crudState[key] = { page: 1, search: "", perPage: 10 };
  return crudState[key];
}

async function renderCrudView(cfg, root = document.getElementById("viewRoot")) {
  const st = ensureState(cfg.key);
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>${cfg.title}</h3>
        <div class="toolbar">
          <input type="search" id="search_${cfg.key}" placeholder="بحث..." value="${escapeHtml(st.search)}">
          <button class="btn btn-primary btn-sm" id="add_${cfg.key}">+ إضافة ${cfg.singular}</button>
        </div>
      </div>
      <div id="stats_${cfg.key}"></div>
      <div class="table-scroll">
        <table>
          <thead><tr>${cfg.columns.map((c) => `<th>${c.label}</th>`).join("")}<th>إجراءات</th></tr></thead>
          <tbody id="tbody_${cfg.key}"><tr class="loading-row"><td colspan="${cfg.columns.length + 1}"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
      <div id="pg_${cfg.key}"></div>
    </div>`;

  document.getElementById(`add_${cfg.key}`).onclick = () => openCrudForm(cfg, null);
  const searchInput = document.getElementById(`search_${cfg.key}`);
  let debounce;
  searchInput.addEventListener("input", () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      st.search = searchInput.value;
      st.page = 1;
      loadCrudList(cfg);
    }, 400);
  });

  await loadCrudList(cfg);
}

async function loadCrudList(cfg) {
  const st = ensureState(cfg.key);
  const tbody = document.getElementById(`tbody_${cfg.key}`);
  if (!tbody) return; // the view isn't the currently active tab (e.g. edited from a drill-down modal) - nothing to refresh
  tbody.innerHTML = `<tr class="loading-row"><td colspan="${cfg.columns.length + 1}"><span class="spinner spinner-dark"></span></td></tr>`;
  try {
    const res = await api.get(NS, cfg.listPath + qs({ page: st.page, limit: st.perPage, search: st.search }));
    const { items, page, totalPages, total, statistics } = extractList(res);
    st.page = page || st.page;
    document.getElementById(`stats_${cfg.key}`).innerHTML = statsBarHtml(statistics);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}"><div class="empty-state">لا توجد بيانات</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map(
          (row) => `<tr>
            ${cfg.columns.map((c) => `<td>${c.render(row)}</td>`).join("")}
            <td class="row-actions">
              <button class="btn btn-outline btn-sm" data-act="edit" data-id="${row.id}">تعديل</button>
              <button class="btn btn-danger btn-sm" data-act="del" data-id="${row.id}">حذف</button>
              ${cfg.extraActions ? cfg.extraActions(row) : ""}
            </td>
          </tr>`
        )
        .join("");
      tbody.querySelectorAll("[data-act=edit]").forEach(
        (b) =>
          (b.onclick = () => openCrudEditForm(cfg, items.find((i) => String(i.id) === b.dataset.id)))
      );
      tbody.querySelectorAll("[data-act=del]").forEach(
        (b) =>
        (b.onclick = () =>
          confirmDialog(`هل تريد حذف هذا العنصر (${cfg.singular})؟`, async () => {
            await api.del(NS, cfg.listPath, { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadCrudList(cfg);
          }))
      );
      if (cfg.wireExtra) cfg.wireExtra(items, cfg);
    }
    const pgEl = document.getElementById(`pg_${cfg.key}`);
    pgEl.innerHTML = paginationHtml(st.page, totalPages, total, st.perPage);
    wirePagination(pgEl, st, () => loadCrudList(cfg));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

/**
 * Opens the edit modal after fetching the full single record from the `/show` endpoint.
 * The list endpoints select a reduced set of columns (e.g. teachers' list query omits `video`,
 * `experience`, `biography`, `description`), so editing straight from the list row would silently
 * wipe/blank those fields in the form. Falls back to the list row if the fetch fails.
 */
async function openCrudEditForm(cfg, listRow, ctx) {
  let row = listRow;
  if (cfg.showPath) {
    try {
      const res = await api.get(NS, cfg.showPath + qs({ id: listRow.id }));
      row = res.data || res || listRow;
    } catch (err) {
      // fall back silently to the (possibly incomplete) list row
    }
  }
  openCrudForm(cfg, row, ctx);
}

function openCrudForm(cfg, row, ctx) {
  const isEdit = !!row;
  Modal.open({
    title: (isEdit ? "تعديل " : "إضافة ") + cfg.singular,
    bodyHtml: cfg.formHtml(row, ctx),
    footerHtml: `
      <button class="btn btn-outline" id="crudCancel">إلغاء</button>
      <button class="btn btn-primary" id="crudSave">${isEdit ? "حفظ التعديلات" : "إضافة"}</button>`,
  });
  if (cfg.afterRender) cfg.afterRender(row, ctx);
  document.getElementById("crudCancel").onclick = () => Modal.close();
  document.getElementById("crudSave").onclick = async () => {
    const btn = document.getElementById("crudSave");
    try {
      const payload = cfg.collect(row, ctx);
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, cfg.listPath, await buildRequestBody(payload, "PUT"));
      } else {
        await api.post(NS, cfg.listPath, await buildRequestBody(payload, "POST"));
      }
      toast(isEdit ? "تم تحديث البيانات" : "تمت الإضافة بنجاح");
      Modal.close();
      loadCrudList(cfg);
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ التعديلات" : "إضافة";
    }
  };
}

/* ---------- Students ---------- */
const studentsCfg = {
  key: "students",
  title: "الطلاب",
  singular: "طالب",
  listPath: "/admin/students",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name) },
    { label: "البريد", render: (r) => escapeHtml(r.email) },
    { label: "الهاتف", render: (r) => escapeHtml(r.phone || "-") },
    { label: "السن", render: (r) => r.age ?? "-" },
    { label: "المدينة", render: (r) => escapeHtml(translatedName(r.city)) },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
    { label: "الإيميل", render: (r) => verifiedBadge(r.is_email_verified) },
  ],
  formHtml(row) {
    return `
      ${row ? "" : `<div class="field"><label>البريد الإلكتروني *</label><input type="email" id="f_email" required></div>`}
      <div class="field"><label>الاسم *</label><input type="text" id="f_name" required value="${escapeHtml(row?.name || "")}"></div>
      ${row ? "" : `<div class="field"><label>كلمة المرور *</label><input type="password" id="f_password" required></div>`}
      <div class="grid-2">
        <div class="field"><label>الهاتف</label><input type="text" id="f_phone" value="${escapeHtml(row?.phone || "")}"></div>
        <div class="field"><label>السن</label><input type="number" id="f_age" value="${row?.age ?? ""}"></div>
      </div>
      <div class="grid-2">
        ${asyncSelectHtml("f_city", { label: "المدينة", currentId: row?.city?.id ?? row?.city_id ?? "", currentLabel: row?.city ? translatedName(row.city, "ar") : "" })}
        ${asyncSelectHtml("f_school", { label: "المدرسة", currentId: row?.school?.id ?? row?.school_id ?? "", currentLabel: row?.school ? translatedName(row.school, "ar") : "" })}
      </div>
      ${imageFieldHtml("f_image", row?.image || "")}
      <div class="grid-2">
        <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>
        <div class="field"><label>الإيميل مؤكد؟</label><select id="f_is_email_verified"><option value="1" ${row?.is_email_verified ? "selected" : ""}>نعم</option><option value="0" ${!row?.is_email_verified ? "selected" : ""}>لا</option></select></div>
      </div>`;
  },
  afterRender() {
    wireImageField("f_image");
    wireAsyncSelect("f_city", fetchCities, { resolveLabel: resolveCityLabel });
    wireAsyncSelect("f_school", fetchSchools, { resolveLabel: resolveSchoolLabel });
  },
  collect(row) {
    const p = {
      name: document.getElementById("f_name").value.trim(),
      phone: document.getElementById("f_phone").value.trim(),
      age: Number(document.getElementById("f_age").value) || undefined,
      city_id: readAsyncSelect("f_city"),
      school_id: readAsyncSelect("f_school"),
      image: readImageField("f_image") || undefined,
      is_active: Number(document.getElementById("f_is_active").value),
      is_email_verified: Number(document.getElementById("f_is_email_verified").value),
    };
    if (!row) {
      p.email = document.getElementById("f_email").value.trim();
      p.password = document.getElementById("f_password").value;
    }
    return p;
  },
};

/* ---------- Teachers ---------- */
let subjectsCache = null;
async function getSubjectsCache() {
  if (subjectsCache) return subjectsCache;
  try {
    const res = await api.get(NS, "/admin/subjects" + qs({ limit: 100 }));
    subjectsCache = extractList(res).items.map((s) => ({ id: s.id, label: translatedName(s) }));
  } catch (e) {
    subjectsCache = [];
  }
  return subjectsCache;
}

const teachersCfg = {
  key: "teachers",
  title: "المعلمون",
  singular: "معلم",
  listPath: "/admin/teachers",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name) },
    { label: "البريد", render: (r) => escapeHtml(r.email) },
    { label: "المواد", render: (r) => (r.subjects || []).map((s) => translatedName(s)).join("، ") || "-" },
    { label: "التخصص", render: (r) => escapeHtml(r.specialization || "-") },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
    { label: "الإيميل", render: (r) => verifiedBadge(r.is_email_verified) },
  ],
  formHtml(row) {
    return `
      ${row ? "" : `<div class="field"><label>البريد الإلكتروني *</label><input type="email" id="f_email" required></div>`}
      <div class="field"><label>الاسم *</label><input type="text" id="f_name" required value="${escapeHtml(row?.name || "")}"></div>
      ${row ? "" : `<div class="field"><label>كلمة المرور *</label><input type="password" id="f_password" required></div>`}
      <div class="grid-2">
        <div class="field"><label>واتساب</label><input type="text" id="f_whatsapp" value="${escapeHtml(row?.whatsapp || "")}"></div>
        ${asyncSelectHtml("f_city", { label: "المدينة", currentId: row?.city?.id ?? row?.city_id ?? "", currentLabel: row?.city ? translatedName(row.city, "ar") : "" })}
      </div>
      <div class="field"><label>التخصص</label><input type="text" id="f_specialization" value="${escapeHtml(row?.specialization || "")}"></div>
      <div class="field"><label>المواد الدراسية</label><div id="ms_subjects">جار التحميل...</div></div>
      ${imageFieldHtml("f_image", row?.image || "")}
      ${imageFieldHtml("f_video", row?.video || "", { kind: "video", label: "الفيديو التعريفي (اختياري)" })}
      <div class="grid-2">
        <div class="field"><label>مفعّل / موافقة الأدمن؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>
        <div class="field"><label>الإيميل مؤكد؟</label><select id="f_is_email_verified"><option value="1" ${row?.is_email_verified ? "selected" : ""}>نعم</option><option value="0" ${!row?.is_email_verified ? "selected" : ""}>لا</option></select></div>
      </div>`;
  },
  async afterRender(row) {
    wireImageField("f_image");
    wireImageField("f_video");
    wireAsyncSelect("f_city", fetchCities, { resolveLabel: resolveCityLabel });
    const subjects = await getSubjectsCache();
    const selected = (row?.subjects || []).map((s) => s.id);
    document.getElementById("ms_subjects").outerHTML = `<div id="ms_subjects">${multiselectHtml("subjects_box", subjects, selected)}</div>`;
  },
  collect(row) {
    const p = {
      name: document.getElementById("f_name").value.trim(),
      whatsapp: document.getElementById("f_whatsapp").value.trim(),
      city_id: readAsyncSelect("f_city"),
      specialization: document.getElementById("f_specialization").value.trim(),
      subject_ids: readMultiselect("subjects_box"),
      image: readImageField("f_image") || undefined,
      video: readImageField("f_video") || undefined,
      is_active: Number(document.getElementById("f_is_active").value),
      is_email_verified: Number(document.getElementById("f_is_email_verified").value),
    };
    if (!row) {
      p.email = document.getElementById("f_email").value.trim();
      p.password = document.getElementById("f_password").value;
    }
    return p;
  },
};

/* ---------- Parents ---------- */
const parentsCfg = {
  key: "parents",
  title: "أولياء الأمور",
  singular: "ولي أمر",
  listPath: "/admin/parents",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name) },
    { label: "البريد", render: (r) => escapeHtml(r.email) },
    { label: "المدينة", render: (r) => escapeHtml(translatedName(r.city)) },
    { label: "الأبناء المرتبطون", render: (r) => (r.students || r.children || []).map((s) => escapeHtml(s.name)).join("، ") || "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
    { label: "الإيميل", render: (r) => verifiedBadge(r.is_email_verified) },
  ],
  extraActions: (row) => `<button class="btn btn-success btn-sm" data-act="link" data-id="${row.id}">ربط طالب</button>`,
  wireExtra(items) {
    document.querySelectorAll(`#tbody_parents [data-act=link]`).forEach(
      (b) =>
        (b.onclick = () => openLinkStudentModal(items.find((i) => String(i.id) === b.dataset.id)))
    );
  },
  formHtml(row) {
    return `
      ${row ? "" : `<div class="field"><label>البريد الإلكتروني *</label><input type="email" id="f_email" required></div>`}
      <div class="field"><label>الاسم *</label><input type="text" id="f_name" required value="${escapeHtml(row?.name || "")}"></div>
      ${row ? "" : `<div class="field"><label>كلمة المرور *</label><input type="password" id="f_password" required></div>`}
      ${asyncSelectHtml("f_city", { label: "المدينة", currentId: row?.city?.id ?? row?.city_id ?? "", currentLabel: row?.city ? translatedName(row.city, "ar") : "" })}
      ${imageFieldHtml("f_image", row?.image || "")}
      <div class="grid-2">
        <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>
        <div class="field"><label>الإيميل مؤكد؟</label><select id="f_is_email_verified"><option value="1" ${row?.is_email_verified ? "selected" : ""}>نعم</option><option value="0" ${!row?.is_email_verified ? "selected" : ""}>لا</option></select></div>
      </div>`;
  },
  afterRender() {
    wireImageField("f_image");
    wireAsyncSelect("f_city", fetchCities, { resolveLabel: resolveCityLabel });
  },
  collect(row) {
    const p = {
      name: document.getElementById("f_name").value.trim(),
      city_id: readAsyncSelect("f_city"),
      image: readImageField("f_image") || undefined,
      is_active: Number(document.getElementById("f_is_active").value),
      is_email_verified: Number(document.getElementById("f_is_email_verified").value),
    };
    if (!row) {
      p.email = document.getElementById("f_email").value.trim();
      p.password = document.getElementById("f_password").value;
    }
    return p;
  },
};

function openLinkStudentModal(parentRow) {
  const alreadyLinked = new Set((parentRow.students || parentRow.children || []).map((s) => s.id));
  const selected = new Map(); // id -> label
  const st = { page: 1, perPage: 10, search: "" };

  const renderChips = () => {
    const box = document.getElementById("lkChips");
    if (!box) return;
    box.innerHTML = selected.size
      ? Array.from(selected.entries())
        .map(([id, label]) => `<span class="chip">${escapeHtml(label)}<button type="button" data-remove="${id}">&times;</button></span>`)
        .join("")
      : `<span class="text-muted" style="font-size:12.5px">لسه محددتش أي طالب</span>`;
    box.querySelectorAll("[data-remove]").forEach(
      (b) => (b.onclick = () => { selected.delete(Number(b.dataset.remove)); renderChips(); document.querySelectorAll(`#lkResults input[value="${b.dataset.remove}"]`).forEach((cb) => (cb.checked = false)); })
    );
  };

  const renderResults = (items) => {
    const box = document.getElementById("lkResults");
    if (!items.length) {
      box.innerHTML = `<div class="text-muted" style="font-size:12.5px;padding:8px 2px">لا يوجد طلاب</div>`;
      return;
    }
    box.innerHTML = items
      .map((s) => {
        const linked = alreadyLinked.has(s.id);
        const checked = selected.has(s.id);
        return `<label style="${linked ? "opacity:.5" : ""}">
          <input type="checkbox" value="${s.id}" data-label="${escapeHtml(s.name)} — ${escapeHtml(s.email)}" ${checked ? "checked" : ""} ${linked ? "disabled" : ""}>
          ${escapeHtml(s.name)} — ${escapeHtml(s.email)} ${linked ? "(مرتبط بالفعل)" : ""}
        </label>`;
      })
      .join("");
    box.querySelectorAll("input[type=checkbox]").forEach((cb) => {
      cb.onchange = () => {
        const id = Number(cb.value);
        if (cb.checked) selected.set(id, cb.dataset.label);
        else selected.delete(id);
        renderChips();
      };
    });
  };

  async function loadStudents() {
    const box = document.getElementById("lkResults");
    box.innerHTML = `<div style="padding:14px;text-align:center"><span class="spinner spinner-dark"></span></div>`;
    try {
      const res = await api.get(NS, "/admin/students" + qs({ page: st.page, limit: st.perPage, search: st.search }));
      const { items, page, totalPages, total } = extractList(res);
      st.page = page || st.page;
      renderResults(items);
      const pgEl = document.getElementById("lkPg");
      pgEl.innerHTML = paginationHtml(st.page, totalPages, total, st.perPage);
      wirePagination(pgEl, st, loadStudents);
    } catch (err) {
      box.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
    }
  }

  Modal.open({
    title: "ربط طالب بولي الأمر",
    size: "modal-lg",
    bodyHtml: `
      <div class="field">
        <label>ولي الأمر</label>
        <div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">
          ${escapeHtml(parentRow.name)} <span class="text-muted">— ${escapeHtml(parentRow.email)}</span>
        </div>
      </div>
      <div class="field">
        <label>فلترة (اختياري)</label>
        <input type="search" id="lkSearch" placeholder="ابحث بالاسم أو البريد...">
      </div>
      <div class="multiselect-box" id="lkResults" style="margin-bottom:0;max-height:280px"></div>
      <div id="lkPg"></div>
      <div class="field" style="margin-top:10px">
        <label>الطلاب المحددون</label>
        <div class="chips" id="lkChips"></div>
      </div>`,
    footerHtml: `<button class="btn btn-outline" id="lkCancel">إلغاء</button><button class="btn btn-primary" id="lkSave">ربط الطلاب المحددين</button>`,
  });

  renderChips();
  loadStudents();
  let debounce;
  document.getElementById("lkSearch").addEventListener("input", (e) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      st.search = e.target.value.trim();
      st.page = 1;
      loadStudents();
    }, 350);
  });

  document.getElementById("lkCancel").onclick = () => Modal.close();
  document.getElementById("lkSave").onclick = async () => {
    if (!selected.size) return toast("حدد طالب واحد على الأقل", "error");
    const btn = document.getElementById("lkSave");
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span>`;
    const ids = Array.from(selected.keys());
    const results = await Promise.allSettled(
      ids.map((studentId) => api.post(NS, "/admin/parents/link-student", { parent_id: parentRow.id, student_id: studentId }))
    );
    const failed = results.filter((r) => r.status === "rejected").length;
    if (failed) toast(`تم ربط ${ids.length - failed} من ${ids.length} طالب، وفشل ${failed}`, "error");
    else toast(`تم ربط ${ids.length} طالب بنجاح`);
    Modal.close();
    loadCrudList(parentsCfg);
  };
}

/* ---------- Admins ---------- */
const adminsCfg = {
  key: "admins",
  title: "المسؤولون",
  singular: "مسؤول",
  listPath: "/admin/admins",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name) },
    { label: "البريد", render: (r) => escapeHtml(r.email) },
    { label: "الرول", render: (r) => escapeHtml(r.role?.name || r.role || "-") },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row) {
    return `
      ${row ? "" : `<div class="field"><label>البريد الإلكتروني *</label><input type="email" id="f_email" required></div>`}
      <div class="field"><label>الاسم *</label><input type="text" id="f_name" required value="${escapeHtml(row?.name || "")}"></div>
      ${row ? "" : `<div class="field"><label>كلمة المرور *</label><input type="password" id="f_password" required></div>`}
      ${asyncSelectHtml("f_role", { label: "الرول", currentId: row?.role_id ?? row?.role?.id ?? "", currentLabel: row?.role?.name || "" })}
      ${imageFieldHtml("f_image", row?.image || "")}
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireImageField("f_image");
    wireAsyncSelect("f_role", fetchRoles);
  },
  collect(row) {
    const p = {
      name: document.getElementById("f_name").value.trim(),
      role_id: readAsyncSelect("f_role"),
      image: readImageField("f_image") || undefined,
      is_active: Number(document.getElementById("f_is_active").value),
    };
    if (!row) {
      p.email = document.getElementById("f_email").value.trim();
      p.password = document.getElementById("f_password").value;
      p.role = "admin";
    }
    return p;
  },
};

const reportTypeMap = {
  academic_performance: { label: "أداء أكاديمي", class: "badge-primary" },
  behavior_discipline: { label: "سلوك وانضباط", class: "badge-purple" },
  exam_result: { label: "نتيجة امتحان", class: "badge-warning" },
  attendance: { label: "حضور وغياب", class: "badge-success" },
};

const studentReportsCfg = {
  key: "student-reports",
  title: "التقارير الأكاديمية",
  singular: "تقرير أكاديمي",
  listPath: "/admin/reports",
  showPath: "/admin/reports/show",
  columns: [
    { label: "الطالب", render: (r) => escapeHtml(r.student_name || `#${r.student_id}`) },
    { label: "نوع التقرير", render: (r) => `<span class="badge ${reportTypeMap[r.report_type]?.class || 'badge-secondary'}">${reportTypeMap[r.report_type]?.label || r.report_type}</span>` },
    { label: "عنوان التقرير", render: (r) => escapeHtml(r.title) },
    { label: "المرسل", render: (r) => escapeHtml(r.sender?.name || "-") },
    { label: "المادة", render: (r) => escapeHtml(r.subject_name || "عام / إدارة") },
    { label: "الدرجة والتقييم", render: (r) => r.score !== null ? `${r.score} / ${r.max_score || 100} (${escapeHtml(r.grade_label || "")})` : (r.grade_label || "-") },
    { label: "التاريخ", render: (r) => escapeHtml(r.report_date || r.created_at || "") },
  ],
  formHtml(row) {
    const strengthsStr = Array.isArray(row?.strengths) ? row.strengths.join(", ") : "";
    const improvementsStr = Array.isArray(row?.improvements) ? row.improvements.join(", ") : "";
    return `
      ${asyncSelectHtml("f_rep_student", { label: "الطالب *", currentId: row?.student_id || "", currentLabel: row?.student_name || "" })}
      <div class="field"><label>نوع التقرير *</label>
        <select id="f_rep_type">
          <option value="academic_performance" ${row?.report_type === "academic_performance" ? "selected" : ""}>أداء أكاديمي</option>
          <option value="behavior_discipline" ${row?.report_type === "behavior_discipline" ? "selected" : ""}>سلوك وانضباط</option>
          <option value="exam_result" ${row?.report_type === "exam_result" ? "selected" : ""}>نتيجة امتحان</option>
          <option value="attendance" ${row?.report_type === "attendance" ? "selected" : ""}>حضور وغياب</option>
        </select>
      </div>
      <div class="field"><label>عنوان التقرير *</label><input type="text" id="f_rep_title" required value="${escapeHtml(row?.title || "")}"></div>
      ${asyncSelectHtml("f_rep_subject", { label: "المادة (اختياري)", currentId: row?.subject_id || "", currentLabel: row?.subject_name || "" })}
      ${asyncSelectHtml("f_rep_teacher", { label: "المعلم (اختياري)", currentId: row?.teacher_id || "", currentLabel: row?.sender?.name || "" })}
      <div class="field"><label>الفصل الدراسي</label><input type="text" id="f_rep_term" value="${escapeHtml(row?.term || "الفصل الدراسي الأول")}"></div>
      <div class="form-row">
        <div class="field"><label>الدرجة المكتسبة</label><input type="number" step="0.1" id="f_rep_score" value="${row?.score ?? ""}"></div>
        <div class="field"><label>الدرجة الكلية</label><input type="number" step="0.1" id="f_rep_max_score" value="${row?.max_score ?? "100"}"></div>
      </div>
      <div class="field"><label>التقدير (مثال: ممتاز، جيد جداً)</label><input type="text" id="f_rep_grade_label" value="${escapeHtml(row?.grade_label || "")}"></div>
      <div class="field"><label>ملاحظات المعلم / الإدارة</label><textarea id="f_rep_desc" rows="3">${escapeHtml(row?.description || "")}</textarea></div>
      <div class="field"><label>نقاط القوة (مفصولة بفواصل)</label><input type="text" id="f_rep_strengths" placeholder="مثال: حل المسائل التحليلية, الدقة في الحسابات" value="${escapeHtml(strengthsStr)}"></div>
      <div class="field"><label>مجالات التطوير (مفصولة بفواصل)</label><input type="text" id="f_rep_improvements" placeholder="مثال: التركيز أكثر في الإحصاء" value="${escapeHtml(improvementsStr)}"></div>
      <div class="field"><label>تاريخ التقرير</label><input type="date" id="f_rep_date" value="${row?.report_date || new Date().toISOString().split('T')[0]}"></div>`;
  },
  afterRender(row) {
    wireAsyncSelect("f_rep_student", fetchAdminStudents);
    wireAsyncSelect("f_rep_subject", fetchAdminSubjects);
    wireAsyncSelect("f_rep_teacher", fetchAdminTeachers);
  },
  collect() {
    const parseTags = (str) => str.split(",").map((s) => s.trim()).filter(Boolean);
    return {
      student_id: readAsyncSelect("f_rep_student"),
      report_type: document.getElementById("f_rep_type").value,
      title: document.getElementById("f_rep_title").value.trim(),
      subject_id: readAsyncSelect("f_rep_subject") || undefined,
      teacher_id: readAsyncSelect("f_rep_teacher") || undefined,
      term: document.getElementById("f_rep_term").value.trim(),
      score: document.getElementById("f_rep_score").value !== "" ? Number(document.getElementById("f_rep_score").value) : undefined,
      max_score: document.getElementById("f_rep_max_score").value !== "" ? Number(document.getElementById("f_rep_max_score").value) : undefined,
      grade_label: document.getElementById("f_rep_grade_label").value.trim(),
      description: document.getElementById("f_rep_desc").value.trim(),
      strengths: parseTags(document.getElementById("f_rep_strengths").value),
      improvements: parseTags(document.getElementById("f_rep_improvements").value),
      report_date: document.getElementById("f_rep_date").value,
    };
  },
};

/* ---------- Courses ---------- */
const coursesCfg = {
  key: "courses",
  title: "الكورسات",
  singular: "كورس",
  listPath: "/admin/courses",
  showPath: "/admin/courses/show",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name_ar || translatedName(r, "ar")) },
    { label: "المادة", render: (r) => escapeHtml(translatedName(r.subject, "ar")) || "-" },
    { label: "التصنيف", render: (r) => escapeHtml(translatedName(r.category, "ar")) || "-" },
    { label: "السنة الدراسية", render: (r) => escapeHtml(translatedName(r.academic_year, "ar")) || "-" },
    { label: "النوع", render: (r) => (r.type === "live" ? "مباشر" : r.type === "offline" ? "أوفلاين" : r.type === "general" ? "عام" : "مسجل") },
    { label: "السعر", render: (r) => (r.price > 0 ? r.price : "مجاني") },
    { label: "المعلمون", render: (r) => (r.teachers || []).map((t) => escapeHtml(t.name)).join("، ") || "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  extraActions: (row) => `<button class="btn btn-outline btn-sm" data-act="content" data-id="${row.id}">📦 عرض الوحدات</button>`,
  wireExtra(items) {
    document.querySelectorAll(`#tbody_courses [data-act=content]`).forEach(
      (b) => (b.onclick = () => showCourseUnitsPage(items.find((i) => String(i.id) === b.dataset.id)))
    );
  },
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="f_name_ar" required value="${escapeHtml(row?.name_ar || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية</label><input type="text" id="f_name_en" value="${escapeHtml(row?.name_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="f_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="f_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      <div class="grid-2">
        ${asyncSelectHtml("f_subject", { label: "المادة الدراسية *", required: true, currentId: row?.subject_id ?? "", currentLabel: row?.subject ? translatedName(row.subject, "ar") : "" })}
        ${asyncSelectHtml("f_category", { label: "التصنيف", currentId: row?.category_id ?? "", currentLabel: row?.category ? translatedName(row.category, "ar") : "" })}
      </div>
      <div class="grid-3">
        ${asyncSelectHtml("f_academic_year", { label: "السنة الدراسية", currentId: row?.academic_year_id ?? "", currentLabel: row?.academic_year ? translatedName(row.academic_year, "ar") : "" })}
        <div class="field">
          <label>نوع الكورس</label>
          <select id="f_type">
            <option value="recorded" ${row?.type === "recorded" || !row?.type ? "selected" : ""}>مسجل (Recorded)</option>
            <option value="live" ${row?.type === "live" ? "selected" : ""}>مباشر (Live)</option>
            <option value="offline" ${row?.type === "offline" ? "selected" : ""}>أوفلاين (Offline)</option>
            <option value="general" ${row?.type === "general" ? "selected" : ""}>عام (General)</option>
          </select>
        </div>
        <div class="field"><label>السعر (0 = مجاني)</label><input type="number" step="0.01" id="f_price" value="${row?.price ?? 0}"></div>
      </div>
      <div class="field"><label>المعلمون المسؤولون عن الكورس</label><div id="ms_course_teachers">جار التحميل...</div></div>
      ${imageFieldHtml("f_image", row?.image || "")}
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  async afterRender(row) {
    wireImageField("f_image");
    wireAsyncSelect("f_subject", fetchAdminSubjects, { resolveLabel: resolveSubjectLabel });
    wireAsyncSelect("f_category", fetchCourseCategories, { resolveLabel: resolveCourseCategoryLabel });
    wireAsyncSelect("f_academic_year", fetchAcademicYears, { resolveLabel: resolveAcademicYearLabel });
    const teachers = await getTeachersCache();
    const selected = (row?.teachers || []).map((t) => t.id);
    document.getElementById("ms_course_teachers").outerHTML = `<div id="ms_course_teachers">${multiselectHtml("course_teachers_box", teachers, selected)}</div>`;
  },
  collect() {
    return {
      name_ar: document.getElementById("f_name_ar").value.trim(),
      name_en: document.getElementById("f_name_en").value.trim(),
      description_ar: document.getElementById("f_desc_ar").value.trim(),
      description_en: document.getElementById("f_desc_en").value.trim(),
      subject_id: readAsyncSelect("f_subject"),
      category_id: readAsyncSelect("f_category"),
      academic_year_id: readAsyncSelect("f_academic_year"),
      type: document.getElementById("f_type").value,
      price: Number(document.getElementById("f_price").value) || 0,
      teacher_ids: readMultiselect("course_teachers_box"),
      image: readImageField("f_image") || undefined,
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

/* ---------- Banners ---------- */
const bannersCfg = {
  key: "banners",
  title: "البانرات الإعلانية",
  singular: "بانر",
  listPath: "/admin/banners",
  showPath: "/admin/banners/show",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image_ar || r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title || "-") },
    { label: "النوع", render: (r) => (r.type === "video" ? "فيديو" : "صورة") },
    { label: "الرابط", render: (r) => escapeHtml(r.link || "-") },
    { label: "المشاهدات", render: (r) => r.views_count ?? 0 },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  extraActions: (row) => `<button class="btn btn-outline btn-sm" data-act="views" data-id="${row.id}">📊 الإحصائيات</button>`,
  wireExtra(items) {
    document.querySelectorAll(`#tbody_banners [data-act=views]`).forEach(
      (b) => (b.onclick = () => openBannerStatsModal(items.find((i) => String(i.id) === b.dataset.id)))
    );
  },
  formHtml(row, ctx) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="f_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="f_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الرابط عند الضغط</label><input type="text" id="f_link" value="${escapeHtml(row?.link || "")}" placeholder="https://..."></div>
        <div class="field"><label>النوع</label><select id="f_type"><option value="image" ${row?.type !== "video" ? "selected" : ""}>صورة</option><option value="video" ${row?.type === "video" ? "selected" : ""}>فيديو</option></select></div>
      </div>
      ${imageFieldHtml("f_image_ar", row?.image_ar || "", { label: "الصورة/الفيديو (عربي) *" })}
      ${imageFieldHtml("f_image_en", row?.image_en || "", { label: "الصورة/الفيديو (إنجليزي، اختياري)" })}
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  afterRender() {
    wireImageField("f_image_ar");
    wireImageField("f_image_en");
  },
  collect() {
    return {
      title_ar: document.getElementById("f_title_ar").value.trim(),
      title_en: document.getElementById("f_title_en").value.trim(),
      link: document.getElementById("f_link").value.trim(),
      type: document.getElementById("f_type").value,
      image_ar: readImageField("f_image_ar") || undefined,
      image_en: readImageField("f_image_en") || undefined,
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

async function openBannerStatsModal(row) {
  Modal.open({
    title: `إحصائيات مشاهدات: ${row.title_ar || row.title || ""}`,
    bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`,
    footerHtml: `<button class="btn btn-outline" id="bsClose">إغلاق</button>`,
  });
  document.getElementById("bsClose").onclick = () => Modal.close();
  try {
    const res = await api.get(NS, "/admin/banners/show" + qs({ id: row.id }));
    const b = res.data || res;
    const breakdown = b.views_breakdown || {};
    document.getElementById("modalBody").innerHTML = `
      <div class="stats-bar" style="padding:0 0 4px">
        ${Object.entries(breakdown)
        .map(
          ([k, v]) => `<div class="stat-chip stat-chip-primary">
              <span class="stat-chip-icon">👁️</span>
              <span class="stat-chip-text"><span class="stat-chip-value">${v}</span><span class="stat-chip-label">${escapeHtml(k)}</span></span>
            </div>`
        )
        .join("")}
      </div>`;
  } catch (err) {
    document.getElementById("modalBody").innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ---------- Shared: unit/course/subject linkage fields + teacher_ids multiselect, reused by
   Units / Videos / Books / Exams (they all link to a unit and/or course and/or subject, and can
   be assigned to one or more teachers) ---------- */
function lockedFieldHtml(label, icon, text) {
  return `
    <div class="field">
      <label>${label}</label>
      <div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">
        ${icon} ${escapeHtml(text)} <span class="text-muted" style="font-size:12px">(محدد تلقائيًا)</span>
      </div>
    </div>`;
}

/**
 * When opened from a drill-down (e.g. "+ إضافة" inside a unit's content page), `ctx.presetLinkage`
 * is already known - so instead of asking the admin to re-pick the unit/course/subject they just
 * came from, show it locked and read-only. Only the standalone "+ إضافة" from the main list (no
 * preset) shows the full async-select pickers.
 */
function contentLinkageFieldsHtml(row, ctx) {
  const preset = ctx?.presetLinkage;
  if (preset) {
    return lockedFieldHtml("الوحدة", "📦", preset.label);
  }
  return `
    <div class="grid-2">
      ${asyncSelectHtml("f_unit", { label: "الوحدة (اختياري، يحدد المادة والكورس تلقائيًا)", currentId: row?.unit_id ?? "", currentLabel: row?.unit ? unitLabel(row.unit) : "" })}
      ${asyncSelectHtml("f_course", { label: "الكورس", currentId: row?.course_id ?? "", currentLabel: row?.course ? translatedName(row.course, "ar") : "" })}
    </div>
    ${asyncSelectHtml("f_subject", { label: "المادة الدراسية", currentId: row?.subject_id ?? "", currentLabel: row?.subject ? translatedName(row.subject, "ar") : "" })}`;
}
function wireContentLinkageFields(row, ctx) {
  if (ctx?.presetLinkage) return; // locked read-only text - nothing to wire
  wireAsyncSelect("f_unit", fetchUnits, { resolveLabel: resolveUnitLabel });
  wireAsyncSelect("f_course", fetchAdminCourses, { resolveLabel: resolveCourseLabel });
  wireAsyncSelect("f_subject", fetchAdminSubjects, { resolveLabel: resolveSubjectLabel });
}
function collectContentLinkageFields(ctx) {
  if (ctx?.presetLinkage) {
    return { unit_id: ctx.presetLinkage.unit_id };
  }
  return {
    unit_id: readAsyncSelect("f_unit"),
    course_id: readAsyncSelect("f_course"),
    subject_id: readAsyncSelect("f_subject"),
  };
}
async function renderTeacherIdsBox(row) {
  const teachers = await getTeachersCache();
  const selected = (row?.teachers || []).map((t) => t.id);
  document.getElementById("ms_content_teachers").outerHTML = `<div id="ms_content_teachers">${multiselectHtml("content_teachers_box", teachers, selected)}</div>`;
}

/* ---------- Units ---------- */
const unitsCfg = {
  key: "units",
  title: "الوحدات",
  singular: "وحدة",
  listPath: "/admin/units",
  showPath: "/admin/units/show",
  columns: [
    { label: "الاسم", render: (r) => escapeHtml(r.name_ar || r.name) },
    { label: "الكود", render: (r) => escapeHtml(r.code || "-") },
    { label: "الكورس", render: (r) => escapeHtml(courseLabel(r.course || {})) || "-" },
    { label: "المادة", render: (r) => escapeHtml(translatedName(r.subject, "ar")) || "-" },
    { label: "المعلمون", render: (r) => (r.teachers || []).map((t) => escapeHtml(t.name)).join("، ") || "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  extraActions: (row) => `
    <button class="btn btn-outline btn-sm" data-act="videos" data-id="${row.id}">🎬 فيديوهات</button>
    <button class="btn btn-outline btn-sm" data-act="books" data-id="${row.id}">📖 كتب</button>
    <button class="btn btn-outline btn-sm" data-act="exams" data-id="${row.id}">📝 امتحانات</button>
    <button class="btn btn-outline btn-sm" data-act="questions" data-id="${row.id}">❓ أسئلة</button>`,
  wireExtra(items) {
    ["videos", "books", "exams", "questions"].forEach((typeKey) => {
      document.querySelectorAll(`#tbody_units [data-act=${typeKey}]`).forEach(
        (b) => (b.onclick = () => showUnitTypePage(items.find((i) => String(i.id) === b.dataset.id), typeKey))
      );
    });
  },
  formHtml(row, ctx) {
    const presetCourse = ctx?.presetLinkage;
    return `
      <div class="grid-2">
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="f_name_ar" required value="${escapeHtml(row?.name_ar || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية</label><input type="text" id="f_name_en" value="${escapeHtml(row?.name_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="f_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="f_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الكود</label><input type="text" id="f_code" value="${escapeHtml(row?.code || "")}"></div>
        ${presetCourse
        ? lockedFieldHtml("الكورس", "📚", presetCourse.label)
        : asyncSelectHtml("f_course", { label: "الكورس *", required: true, currentId: row?.course_id ?? "", currentLabel: row?.course ? courseLabel(row.course) : "" })}
      </div>
      ${asyncSelectHtml("f_subject", { label: "المادة الدراسية *", required: true, currentId: row?.subject_id ?? "", currentLabel: row?.subject ? translatedName(row.subject, "ar") : "" })}
      <div class="field"><label>المعلمون المسؤولون</label><div id="ms_content_teachers">جار التحميل...</div></div>
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  async afterRender(row, ctx) {
    if (!ctx?.presetLinkage) wireAsyncSelect("f_course", fetchAdminCourses, { resolveLabel: resolveCourseLabel });
    wireAsyncSelect("f_subject", fetchAdminSubjects, { resolveLabel: resolveSubjectLabel });
    await renderTeacherIdsBox(row);
  },
  collect(row, ctx) {
    return {
      name_ar: document.getElementById("f_name_ar").value.trim(),
      name_en: document.getElementById("f_name_en").value.trim(),
      description_ar: document.getElementById("f_desc_ar").value.trim(),
      description_en: document.getElementById("f_desc_en").value.trim(),
      code: document.getElementById("f_code").value.trim(),
      course_id: ctx?.presetLinkage ? ctx.presetLinkage.course_id : readAsyncSelect("f_course"),
      subject_id: readAsyncSelect("f_subject"),
      teacher_ids: readMultiselect("content_teachers_box"),
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

/* ---------- Videos ---------- */
const videosCfg = {
  key: "videos",
  title: "الفيديوهات",
  singular: "فيديو",
  listPath: "/admin/videos",
  showPath: "/admin/videos/show",
  columns: [
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title) },
    { label: "المدة", render: (r) => escapeHtml(r.duration || "-") },
    { label: "الوحدة", render: (r) => escapeHtml(unitLabel(r.unit || {})) || "-" },
    { label: "الترتيب", render: (r) => r.order_num ?? "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row, ctx) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="f_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="f_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="f_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="f_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      ${contentLinkageFieldsHtml(row, ctx)}
      <div class="grid-2">
        <div class="field"><label>رابط الفيديو</label><input type="text" id="f_video_url" value="${escapeHtml(row?.video_url || "")}" placeholder="https://..."></div>
        <div class="field"><label>المدة (مثال: 12:30)</label><input type="text" id="f_duration" value="${escapeHtml(row?.duration || "")}"></div>
      </div>
      <div class="field"><label>الترتيب</label><input type="number" id="f_order_num" value="${row?.order_num ?? 1}"></div>
      <div class="field"><label>المعلمون المسؤولون</label><div id="ms_content_teachers">جار التحميل...</div></div>
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  async afterRender(row, ctx) {
    wireContentLinkageFields(row, ctx);
    await renderTeacherIdsBox(row);
  },
  collect(row, ctx) {
    return {
      title_ar: document.getElementById("f_title_ar").value.trim(),
      title_en: document.getElementById("f_title_en").value.trim(),
      description_ar: document.getElementById("f_desc_ar").value.trim(),
      description_en: document.getElementById("f_desc_en").value.trim(),
      ...collectContentLinkageFields(ctx),
      video_url: document.getElementById("f_video_url").value.trim(),
      duration: document.getElementById("f_duration").value.trim(),
      order_num: Number(document.getElementById("f_order_num").value) || 1,
      teacher_ids: readMultiselect("content_teachers_box"),
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

/* ---------- Books ---------- */
const booksCfg = {
  key: "books",
  title: "الكتب",
  singular: "كتاب",
  listPath: "/admin/books",
  showPath: "/admin/books/show",
  columns: [
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title) },
    { label: "النوع", render: (r) => escapeHtml(r.file_type || "-") },
    { label: "الوحدة", render: (r) => escapeHtml(unitLabel(r.unit || {})) || "-" },
    { label: "الترتيب", render: (r) => r.order_num ?? "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row, ctx) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="f_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="f_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="f_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="f_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      ${contentLinkageFieldsHtml(row, ctx)}
      <div class="grid-2">
        <div class="field"><label>نوع الملف</label><input type="text" id="f_file_type" value="${escapeHtml(row?.file_type || "pdf")}"></div>
        <div class="field"><label>الترتيب</label><input type="number" id="f_order_num" value="${row?.order_num ?? 1}"></div>
      </div>
      ${imageFieldHtml("f_file", row?.link || row?.file_path || "", { kind: "file", label: "ملف الكتاب (رابط أو رفع)" })}
      <div class="field"><label>المعلمون المسؤولون</label><div id="ms_content_teachers">جار التحميل...</div></div>
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  async afterRender(row, ctx) {
    wireContentLinkageFields(row, ctx);
    wireImageField("f_file");
    await renderTeacherIdsBox(row);
  },
  collect(row, ctx) {
    return {
      title_ar: document.getElementById("f_title_ar").value.trim(),
      title_en: document.getElementById("f_title_en").value.trim(),
      description_ar: document.getElementById("f_desc_ar").value.trim(),
      description_en: document.getElementById("f_desc_en").value.trim(),
      ...collectContentLinkageFields(ctx),
      file_type: document.getElementById("f_file_type").value.trim(),
      order_num: Number(document.getElementById("f_order_num").value) || 1,
      file: readImageField("f_file") || undefined,
      teacher_ids: readMultiselect("content_teachers_box"),
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

/* ---------- Exams ---------- */
const examsCfg = {
  key: "exams",
  title: "الامتحانات",
  singular: "امتحان",
  listPath: "/admin/exams",
  showPath: "/admin/exams/show",
  columns: [
    { label: "العنوان", render: (r) => escapeHtml(r.title_ar || r.title) },
    { label: "المدة (دقيقة)", render: (r) => r.duration_minutes ?? "-" },
    { label: "الدرجة الكلية", render: (r) => r.total_marks ?? "-" },
    { label: "درجة النجاح", render: (r) => r.pass_marks ?? "-" },
    { label: "الوحدة", render: (r) => escapeHtml(unitLabel(r.unit || {})) || "-" },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  extraActions: (row) => `
    <button class="btn btn-outline btn-sm" data-act="questions" data-id="${row.id}">❓ الأسئلة</button>
    <button class="btn btn-outline btn-sm" data-act="results" data-id="${row.id}">📊 النتائج</button>`,
  wireExtra(items) {
    document.querySelectorAll(`#tbody_exams [data-act=questions]`).forEach(
      (b) => (b.onclick = () => showExamQuestionsPage(items.find((i) => String(i.id) === b.dataset.id)))
    );
    document.querySelectorAll(`#tbody_exams [data-act=results]`).forEach(
      (b) => (b.onclick = () => showExamAttemptsPage(items.find((i) => String(i.id) === b.dataset.id)))
    );
  },
  formHtml(row, ctx) {
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان بالعربية *</label><input type="text" id="f_title_ar" required value="${escapeHtml(row?.title_ar || "")}"></div>
        <div class="field"><label>العنوان بالإنجليزية</label><input type="text" id="f_title_en" value="${escapeHtml(row?.title_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="f_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="f_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      ${contentLinkageFieldsHtml(row, ctx)}
      <div class="grid-2">
        <div class="field"><label>المدة (دقيقة)</label><input type="number" id="f_duration_minutes" value="${row?.duration_minutes ?? 30}"></div>
        <div class="field"><label>الدرجة الكلية</label><input type="number" id="f_total_marks" value="${row?.total_marks ?? 100}"></div>
      </div>
      <div class="field"><label>درجة النجاح</label><input type="number" id="f_pass_marks" value="${row?.pass_marks ?? 50}"></div>
      <div class="field"><label>المعلمون المسؤولون</label><div id="ms_content_teachers">جار التحميل...</div></div>
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  async afterRender(row, ctx) {
    wireContentLinkageFields(row, ctx);
    await renderTeacherIdsBox(row);
  },
  collect(row, ctx) {
    return {
      title_ar: document.getElementById("f_title_ar").value.trim(),
      title_en: document.getElementById("f_title_en").value.trim(),
      description_ar: document.getElementById("f_desc_ar").value.trim(),
      description_en: document.getElementById("f_desc_en").value.trim(),
      ...collectContentLinkageFields(ctx),
      duration_minutes: Number(document.getElementById("f_duration_minutes").value) || 30,
      total_marks: Number(document.getElementById("f_total_marks").value) || 100,
      pass_marks: Number(document.getElementById("f_pass_marks").value) || 50,
      teacher_ids: readMultiselect("content_teachers_box"),
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

/* ================= Drill-down: Course → Units → Videos/Books/Exams =================
 * Lets the admin jump from a course into its units, and from a unit into its content, without
 * leaving the modal flow - "عرض الوحدات" on a course row, "عرض المحتوى" on a unit row.
 */
function openUnitFormPresetCourse(course) {
  openCrudForm(unitsCfg, null, { presetLinkage: { course_id: course.id, label: courseLabel(course) } });
}
function openVideoFormPresetUnit(unit) {
  openCrudForm(videosCfg, null, { presetLinkage: { unit_id: unit.id, label: unitLabel(unit) } });
}
function openBookFormPresetUnit(unit) {
  openCrudForm(booksCfg, null, { presetLinkage: { unit_id: unit.id, label: unitLabel(unit) } });
}
function openExamFormPresetUnit(unit) {
  openCrudForm(examsCfg, null, { presetLinkage: { unit_id: unit.id, label: unitLabel(unit) } });
}

/** Opens a real page (not a modal) listing a course's units, replacing the main content area. */
function showCourseUnitsPage(course) {
  currentView = "course-units";
  customPageRefresh = () => renderCourseUnitsPage(document.getElementById("viewRoot"), course);
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
  document.getElementById("pageTitle").textContent = `وحدات الكورس: ${courseLabel(course)}`;
  renderCourseUnitsPage(document.getElementById("viewRoot"), course);
}

function renderCourseUnitsPage(root, course) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" id="backToCourses">→ رجوع للكورسات</button>
          <h3 class="mb-0">وحدات الكورس: ${escapeHtml(courseLabel(course))}</h3>
        </div>
        <button class="btn btn-primary btn-sm" id="addUnitBtn">+ إضافة وحدة</button>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الاسم</th><th>الكود</th><th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody id="cuTbody"><tr class="loading-row"><td colspan="4"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("backToCourses").onclick = () => navigate("courses");
  document.getElementById("addUnitBtn").onclick = () => openUnitFormPresetCourse(course);
  loadCourseUnitsPageData(course);
}

async function loadCourseUnitsPageData(course) {
  const tbody = document.getElementById("cuTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/admin/units" + qs({ course_id: course.id, limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state">لا توجد وحدات لهذا الكورس بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (u) => `<tr>
          <td>${escapeHtml(u.name_ar || u.name)}</td>
          <td>${escapeHtml(u.code || "-")}</td>
          <td>${statusBadge(u.is_active)}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${u.id}">تعديل</button>
            <button class="btn btn-outline btn-sm" data-act="videos" data-id="${u.id}">🎬</button>
            <button class="btn btn-outline btn-sm" data-act="books" data-id="${u.id}">📖</button>
            <button class="btn btn-outline btn-sm" data-act="exams" data-id="${u.id}">📝</button>
            <button class="btn btn-outline btn-sm" data-act="questions" data-id="${u.id}">❓</button>
            <button class="btn btn-danger btn-sm" data-act="del" data-id="${u.id}">حذف</button>
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openCrudEditForm(unitsCfg, items.find((i) => String(i.id) === b.dataset.id))));
    ["videos", "books", "exams", "questions"].forEach((typeKey) => {
      tbody.querySelectorAll(`[data-act=${typeKey}]`).forEach(
        (b) => (b.onclick = () => showUnitTypePage(items.find((i) => String(i.id) === b.dataset.id), typeKey))
      );
    });
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
      (b.onclick = () =>
        confirmDialog("هل تريد حذف هذه الوحدة؟", async () => {
          await api.del(NS, "/admin/units", { id: Number(b.dataset.id) });
          toast("تم الحذف بنجاح");
          loadCourseUnitsPageData(course);
        }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

const UNIT_CONTENT_TABS = {
  videos: { path: "/admin/videos", cfg: videosCfg, addFn: openVideoFormPresetUnit, colLabels: ["العنوان", "المدة"], cols: (v) => [v.title_ar || v.title, v.duration || "-"] },
  books: { path: "/admin/books", cfg: booksCfg, addFn: openBookFormPresetUnit, colLabels: ["العنوان", "النوع"], cols: (b) => [b.title_ar || b.title, b.file_type || "-"] },
  exams: { path: "/admin/exams", cfg: examsCfg, addFn: openExamFormPresetUnit, colLabels: ["العنوان", "المدة (دقيقة)"], cols: (e) => [e.title_ar || e.title, e.duration_minutes ?? "-"] },
  questions: {
    path: "/admin/questions",
    colLabels: ["السؤال", "النوع", "الدرجة"],
    cols: (q) => [(q.question_text_ar || q.question_text || "").slice(0, 60), questionTypeLabel(q.question_type), q.score],
    addFn: (unit) => openQuestionForm(null, { unit_id: unit.id, label: unitLabel(unit) }),
    customEdit: (q) => openQuestionForm(q, null),
  },
};
const UNIT_CONTENT_TITLES = { videos: "فيديوهات الوحدة", books: "كتب الوحدة", exams: "امتحانات الوحدة", questions: "أسئلة الوحدة" };

/**
 * Each content type (videos/books/exams/questions) inside a unit is its own standalone page with
 * its own back button and its own logic - not a tab switch inside one shared page. Reached via a
 * dedicated action button per type on the unit's row (in the units list or a course's units page).
 */
function showUnitTypePage(unit, typeKey) {
  currentView = "unit-" + typeKey;
  customPageRefresh = () => renderUnitTypePage(document.getElementById("viewRoot"), unit, typeKey);
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
  document.getElementById("pageTitle").textContent = `${UNIT_CONTENT_TITLES[typeKey]}: ${unitLabel(unit)}`;
  renderUnitTypePage(document.getElementById("viewRoot"), unit, typeKey);
}

function renderUnitTypePage(root, unit, typeKey) {
  const meta = UNIT_CONTENT_TABS[typeKey];
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" id="backToUnits">→ رجوع للوحدات</button>
          <h3 class="mb-0">${UNIT_CONTENT_TITLES[typeKey]}: ${escapeHtml(unitLabel(unit))}</h3>
        </div>
        <button class="btn btn-primary btn-sm" id="addContentBtn">+ إضافة</button>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr>${meta.colLabels.map((l) => `<th>${l}</th>`).join("")}<th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody id="ucTbody"><tr class="loading-row"><td colspan="${meta.colLabels.length + 2}"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("backToUnits").onclick = () => navigate("units");
  document.getElementById("addContentBtn").onclick = () => meta.addFn(unit);
  loadUnitContentPageData(unit, meta);
}

async function loadUnitContentPageData(unit, meta) {
  const tbody = document.getElementById("ucTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, meta.path + qs({ unit_id: unit.id, limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="${meta.colLabels.length + 2}"><div class="empty-state">لا يوجد محتوى بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (it) => `<tr>
          ${meta.cols(it).map((v) => `<td>${escapeHtml(String(v))}</td>`).join("")}
          <td>${statusBadge(it.is_active)}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${it.id}">تعديل</button>
            <button class="btn btn-danger btn-sm" data-act="del" data-id="${it.id}">حذف</button>
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=edit]").forEach(
      (b) =>
      (b.onclick = () => {
        const item = items.find((i) => String(i.id) === b.dataset.id);
        if (meta.customEdit) meta.customEdit(item);
        else openCrudEditForm(meta.cfg, item);
      })
    );
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
      (b.onclick = () =>
        confirmDialog("هل تريد حذف هذا العنصر؟", async () => {
          await api.del(NS, meta.path, { id: Number(b.dataset.id) });
          toast("تم الحذف بنجاح");
          loadUnitContentPageData(unit, meta);
        }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="${meta.colLabels.length + 2}"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

/* ================= Questions (inside Exams and inside Units) ================= */
function questionTypeLabel(type) {
  return { mcq: "اختيار من متعدد", true_false: "صح / خطأ", written: "مقالي" }[type] || type || "-";
}

let mcqOptionUidCounter = 0;
function mcqOptionRowHtml(opt) {
  const uid = mcqOptionUidCounter++;
  return `<div class="mcq-option-row" data-uid="${uid}" style="display:flex;gap:10px;align-items:center;margin-bottom:10px">
    <input type="radio" name="qf_correct" value="${uid}" ${opt?.is_correct ? "checked" : ""} title="الإجابة الصحيحة" style="width:18px;height:18px;flex:none;accent-color:var(--primary);cursor:pointer">
    <input type="text" class="opt-ar" placeholder="نص الخيار (عربي)" value="${escapeHtml(opt?.option_text_ar || "")}" style="width:auto;flex:1 1 0;min-width:0">
    <input type="text" class="opt-en" placeholder="نص الخيار (إنجليزي)" value="${escapeHtml(opt?.option_text_en || "")}" style="width:auto;flex:1 1 0;min-width:0">
    <button type="button" class="btn btn-danger btn-sm remove-opt" style="flex:none">×</button>
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

/** The backend only accepts answer/option edits at creation time - editing a question can only
 * change its text, score, teachers, and status. So in edit mode the answer is shown read-only. */
function questionAnswerReadonlyHtml(q) {
  if (q.question_type === "mcq") {
    return `<div class="field"><label>الخيارات (لا يمكن تعديل الإجابات بعد الإنشاء)</label>
      <div style="border:1px solid var(--border);border-radius:8px;padding:6px">
        ${(q.options || [])
        .map(
          (o) => `<div style="padding:6px 8px;${o.is_correct ? "color:var(--success);font-weight:700" : ""}">${o.is_correct ? "✓ " : "— "}${escapeHtml(o.option_text_ar || o.option_text || "")}</div>`
        )
        .join("") || `<div class="text-muted" style="padding:6px 8px">لا توجد خيارات</div>`}
      </div></div>`;
  }
  if (q.question_type === "true_false") {
    return `<div class="field"><label>الإجابة الصحيحة (لا يمكن تعديلها بعد الإنشاء)</label><div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${q.correct_answer ? "صح" : "خطأ"}</div></div>`;
  }
  return `<div class="field"><label>الإجابة النموذجية (لا يمكن تعديلها بعد الإنشاء)</label><div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${escapeHtml(q.model_answer_ar || q.model_answer || "-")}</div></div>`;
}

/**
 * `presetLinkage` is always provided when creating (either `{ exam_id, label }` from the exam's
 * questions page, or `{ unit_id, label }` from a unit's "أسئلة" tab) - there's no standalone
 * "add question" entry point, so the link is always already known and shown locked, never re-picked.
 */
function openQuestionForm(question, presetLinkage) {
  const isEdit = !!question;
  const type = question?.question_type || "mcq";

  Modal.open({
    title: isEdit ? "تعديل سؤال" : "سؤال جديد",
    size: "modal-lg",
    bodyHtml: `
      ${presetLinkage ? lockedFieldHtml(presetLinkage.exam_id ? "الامتحان" : "الوحدة", presetLinkage.exam_id ? "📝" : "📦", presetLinkage.label) : ""}
      <div class="grid-2">
        <div class="field">
          <label>نوع السؤال *</label>
          ${isEdit
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
      <div class="field"><label>المعلمون المسؤولون</label><div id="ms_content_teachers">جار التحميل...</div></div>
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
  renderTeacherIdsBox(question);

  document.getElementById("qfCancel").onclick = () => Modal.close();
  document.getElementById("qfSave").onclick = async () => {
    const btn = document.getElementById("qfSave");
    const payload = {
      question_text_ar: document.getElementById("qf_text_ar").value.trim(),
      question_text_en: document.getElementById("qf_text_en").value.trim(),
      score: Number(document.getElementById("qf_score").value) || 1,
      teacher_ids: readMultiselect("content_teachers_box"),
      is_active: Number(document.getElementById("qf_is_active").value),
    };
    if (isEdit) {
      payload.id = question.id;
    } else {
      payload.question_type = document.getElementById("qf_type").value;
      if (presetLinkage) {
        if (presetLinkage.exam_id) payload.exam_id = presetLinkage.exam_id;
        if (presetLinkage.unit_id) payload.unit_id = presetLinkage.unit_id;
      }
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
      if (isEdit) await api.put(NS, "/admin/questions", payload);
      else await api.post(NS, "/admin/questions", payload);
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

/** Opens a real page (not a modal) listing an exam's questions, replacing the main content area. */
function showExamQuestionsPage(exam) {
  currentView = "exam-questions";
  customPageRefresh = () => renderExamQuestionsPage(document.getElementById("viewRoot"), exam);
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
  document.getElementById("pageTitle").textContent = `أسئلة الامتحان: ${examLabel(exam)}`;
  renderExamQuestionsPage(document.getElementById("viewRoot"), exam);
}

function renderExamQuestionsPage(root, exam) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" id="backToExams">→ رجوع للامتحانات</button>
          <h3 class="mb-0">أسئلة الامتحان: ${escapeHtml(examLabel(exam))}</h3>
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
  document.getElementById("addQuestionBtn").onclick = () => openQuestionForm(null, { exam_id: exam.id, label: examLabel(exam) });
  loadExamQuestionsPageData(exam);
}

async function loadExamQuestionsPageData(exam) {
  const tbody = document.getElementById("eqTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/admin/questions" + qs({ exam_id: exam.id, limit: 50 }));
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
    tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openQuestionForm(items.find((i) => String(i.id) === b.dataset.id), null)));
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
      (b.onclick = () =>
        confirmDialog("هل تريد حذف هذا السؤال؟", async () => {
          await api.del(NS, "/admin/questions", { id: Number(b.dataset.id) });
          toast("تم الحذف بنجاح");
          loadExamQuestionsPageData(exam);
        }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

/* ---------- Certificates ---------- */
const certificatesCfg = {
  key: "certificates",
  title: "الشهادات",
  singular: "شهادة",
  listPath: "/admin/certificates",
  showPath: "/admin/certificates/show",
  columns: [
    { label: "الصورة", render: (r) => `<img class="avatar-sm" src="${escapeHtml(r.image || "")}" onerror="this.style.visibility='hidden'">` },
    { label: "الاسم", render: (r) => escapeHtml(r.name_ar || r.name) },
    { label: "الكود", render: (r) => escapeHtml(r.code || "-") },
    { label: "النوع", render: (r) => escapeHtml(r.certificate_type || "-") },
    { label: "الدرجة المطلوبة", render: (r) => r.score ?? "-" },
    { label: "السنة الدراسية", render: (r) => (r.is_for_all_academic_years ? "كل السنوات" : escapeHtml(translatedName(r.academic_year, "ar")) || "-") },
    { label: "الحالة", render: (r) => statusBadge(r.is_active) },
  ],
  formHtml(row) {
    return `
      <div class="grid-2">
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="f_name_ar" required value="${escapeHtml(row?.name_ar || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية</label><input type="text" id="f_name_en" value="${escapeHtml(row?.name_en || "")}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>الوصف بالعربية</label><textarea id="f_desc_ar" rows="2">${escapeHtml(row?.description_ar || "")}</textarea></div>
        <div class="field"><label>الوصف بالإنجليزية</label><textarea id="f_desc_en" rows="2">${escapeHtml(row?.description_en || "")}</textarea></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>نوع الشهادة</label><input type="text" id="f_cert_type" value="${escapeHtml(row?.certificate_type || "general")}" placeholder="general / completion / excellence ..."></div>
        <div class="field"><label>الدرجة المطلوبة للحصول عليها</label><input type="number" step="0.5" id="f_score" value="${row?.score ?? 0}"></div>
      </div>
      <div class="grid-2">
        ${asyncSelectHtml("f_subject", { label: "المادة الدراسية", currentId: row?.subject_id ?? "", currentLabel: row?.subject ? translatedName(row.subject, "ar") : "" })}
        ${asyncSelectHtml("f_course", { label: "الكورس", currentId: row?.course_id ?? "", currentLabel: row?.course ? courseLabel(row.course) : "" })}
      </div>
      ${asyncSelectHtml("f_academic_year", { label: "السنة الدراسية (اتركها فارغة = كل السنوات)", currentId: row?.academic_year_id ?? "", currentLabel: row?.academic_year ? translatedName(row.academic_year, "ar") : "" })}
      ${imageFieldHtml("f_image", row?.image || "")}
      <div class="field"><label>المعلمون المسؤولون</label><div id="ms_content_teachers">جار التحميل...</div></div>
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`;
  },
  async afterRender(row) {
    wireImageField("f_image");
    wireAsyncSelect("f_subject", fetchAdminSubjects, { resolveLabel: resolveSubjectLabel });
    wireAsyncSelect("f_course", fetchAdminCourses, { resolveLabel: resolveCourseLabel });
    wireAsyncSelect("f_academic_year", fetchAcademicYears, { resolveLabel: resolveAcademicYearLabel });
    await renderTeacherIdsBox(row);
  },
  collect() {
    return {
      name_ar: document.getElementById("f_name_ar").value.trim(),
      name_en: document.getElementById("f_name_en").value.trim(),
      description_ar: document.getElementById("f_desc_ar").value.trim(),
      description_en: document.getElementById("f_desc_en").value.trim(),
      certificate_type: document.getElementById("f_cert_type").value.trim() || "general",
      score: Number(document.getElementById("f_score").value) || 0,
      subject_id: readAsyncSelect("f_subject"),
      course_id: readAsyncSelect("f_course"),
      academic_year_id: readAsyncSelect("f_academic_year"),
      image: readImageField("f_image") || undefined,
      teacher_ids: readMultiselect("content_teachers_box"),
      is_active: Number(document.getElementById("f_is_active").value),
    };
  },
};

/* ---------- Subscription Codes ---------- */
const subscriptionCodesCfg = {
  key: "subscription-codes",
  title: "أكواد الاشتراك",
  singular: "كود اشتراك",
  listPath: "/admin/subscription-codes",
  showPath: "/admin/subscription-codes/show",
  columns: [
    { label: "الكود", render: (r) => `<code>${escapeHtml(r.code)}</code>` },
    { label: "العنوان", render: (r) => escapeHtml(r.title) },
    { label: "السعر", render: (r) => (r.price > 0 ? r.price : "مجاني") },
    { label: "الاستخدامات", render: (r) => `${r.used_count} / ${r.max_uses === 0 ? "∞" : r.max_uses}` },
    { label: "صالح حتى", render: (r) => (r.valid_to ? fmtDate(r.valid_to) : "بدون تاريخ انتهاء") },
    { label: "الحالة", render: (r) => (r.is_valid ? `<span class="badge badge-success">فعّال</span>` : `<span class="badge badge-danger">غير فعّال</span>`) },
  ],
  formHtml(row) {
    const isEdit = !!row;
    return `
      <div class="grid-2">
        <div class="field"><label>العنوان *</label><input type="text" id="f_title" required value="${escapeHtml(row?.title || "")}"></div>
        <div class="field"><label>السعر</label><input type="number" step="0.01" id="f_price" value="${row?.price ?? 0}"></div>
      </div>
      <div class="field"><label>الوصف</label><textarea id="f_description" rows="2">${escapeHtml(row?.description || "")}</textarea></div>
      ${isEdit
        ? `<div class="field"><label>الكود</label><input type="text" id="f_code" value="${escapeHtml(row?.code || "")}"></div>`
        : `<div class="grid-2">
              <div class="field"><label>الكود (اتركه فارغًا للتوليد التلقائي)</label><input type="text" id="f_code"></div>
              <div class="field"><label>عدد الأكواد المطلوب توليدها دفعة واحدة</label><input type="number" id="f_batch_count" value="1" min="1" max="100"></div>
            </div>`
      }
      <div class="grid-2">
        <div class="field"><label>الحد الأقصى للاستخدام (0 = بلا حدود)</label><input type="number" id="f_max_uses" value="${row?.max_uses ?? 1}"></div>
        <div class="field"><label>مدة الصلاحية بالأيام (اختياري)</label><input type="number" id="f_duration_days" value="${row?.duration_days ?? ""}"></div>
      </div>
      <div class="grid-2">
        <div class="field"><label>صالح من</label><input type="datetime-local" id="f_valid_from" value="${row?.valid_from ? row.valid_from.replace(" ", "T").slice(0, 16) : ""}"></div>
        <div class="field"><label>صالح حتى</label><input type="datetime-local" id="f_valid_to" value="${row?.valid_to ? row.valid_to.replace(" ", "T").slice(0, 16) : ""}"></div>
      </div>
      <div class="field"><label>المواد الدراسية المشمولة</label><div id="ms_sc_subjects">جار التحميل...</div></div>
      <div class="field"><label>المعلمون المشمولون</label><div id="ms_sc_teachers">جار التحميل...</div></div>
      <div class="field"><label>الكورسات المشمولة</label><div id="ms_sc_courses">جار التحميل...</div></div>
      <div class="field"><label>مفعّل؟</label><select id="f_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>
      ${row?.redemptions?.length ? `<div class="field"><label>آخر عمليات الاستخدام (${row.redemptions.length})</label><div class="multiselect-box" style="grid-template-columns:1fr">${row.redemptions.map((r) => `<div style="padding:5px 4px;font-size:12.5px">${escapeHtml(r.student_name)} — ${escapeHtml(r.student_email)} — ${fmtDate(r.redeemed_at)}</div>`).join("")}</div></div>` : ""}`;
  },
  async afterRender(row) {
    const [subjects, teachers, courses] = await Promise.all([getSubjectsCacheFlat(), getTeachersCache(), getCoursesCacheFlat()]);
    document.getElementById("ms_sc_subjects").outerHTML = `<div id="ms_sc_subjects">${multiselectHtml("sc_subjects_box", subjects, row?.subject_ids || [])}</div>`;
    document.getElementById("ms_sc_teachers").outerHTML = `<div id="ms_sc_teachers">${multiselectHtml("sc_teachers_box", teachers, row?.teacher_ids || [])}</div>`;
    document.getElementById("ms_sc_courses").outerHTML = `<div id="ms_sc_courses">${multiselectHtml("sc_courses_box", courses, row?.course_ids || [])}</div>`;
  },
  collect(row) {
    const p = {
      title: document.getElementById("f_title").value.trim(),
      description: document.getElementById("f_description").value.trim(),
      price: Number(document.getElementById("f_price").value) || 0,
      code: document.getElementById("f_code").value.trim() || undefined,
      max_uses: Number(document.getElementById("f_max_uses").value) || 0,
      duration_days: Number(document.getElementById("f_duration_days").value) || undefined,
      valid_from: document.getElementById("f_valid_from").value || undefined,
      valid_to: document.getElementById("f_valid_to").value || undefined,
      subject_ids: readMultiselect("sc_subjects_box"),
      teacher_ids: readMultiselect("sc_teachers_box"),
      course_ids: readMultiselect("sc_courses_box"),
      is_active: Number(document.getElementById("f_is_active").value),
    };
    if (!row) p.batch_count = Number(document.getElementById("f_batch_count").value) || 1;
    return p;
  },
};

/* ---------- Student Subscriptions (list + grant new; no edit/delete - backend doesn't support them) ---------- */
async function renderSubscriptionsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>اشتراكات الطلاب</h3>
        <button class="btn btn-primary btn-sm" id="grantSubBtn">+ منح اشتراك جديد</button>
      </div>
      <div id="subStats"></div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الطالب</th><th>النوع</th><th>المواد/المعلمين/الكورسات</th><th>ينتهي في</th><th>الحالة</th></tr></thead>
          <tbody id="subTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
      <div id="subPg"></div>
    </div>`;
  document.getElementById("grantSubBtn").onclick = () => openGrantSubscriptionModal();
  await loadSubscriptionsList();
}

const subState = { page: 1, perPage: 10 };
async function loadSubscriptionsList() {
  const tbody = document.getElementById("subTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/admin/subscriptions" + qs({ page: subState.page, limit: subState.perPage }));
    const { items, page, totalPages, total, statistics } = extractList(res);
    subState.page = page || subState.page;
    document.getElementById("subStats").innerHTML = statsBarHtml(statistics);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد اشتراكات بعد</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map((s) => {
          const scope = [
            ...(s.subjects || []).map((x) => `📘 ${x.name}`),
            ...(s.teachers || []).map((x) => `👨‍🏫 ${x.name}`),
            ...(s.courses || []).map((x) => `📚 ${x.name}`),
          ].join("، ") || "-";
          return `<tr>
            <td>${escapeHtml(s.student?.name || "-")} <span class="text-muted">— ${escapeHtml(s.student?.email || "")}</span></td>
            <td>${escapeHtml(s.subscription_type)}</td>
            <td style="max-width:320px;white-space:normal">${escapeHtml(scope)}</td>
            <td>${s.end_date ? fmtDate(s.end_date) : "بدون تاريخ انتهاء"}</td>
            <td>${s.is_currently_valid ? `<span class="badge badge-success">سارٍ</span>` : `<span class="badge badge-danger">منتهي</span>`}</td>
          </tr>`;
        })
        .join("");
    }
    const pgEl = document.getElementById("subPg");
    pgEl.innerHTML = paginationHtml(subState.page, totalPages, total, subState.perPage);
    wirePagination(pgEl, subState, loadSubscriptionsList);
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

async function openGrantSubscriptionModal() {
  Modal.open({
    title: "منح اشتراك جديد لطالب",
    size: "modal-lg",
    bodyHtml: `
      ${asyncSelectHtml("gs_student", { label: "الطالب *", required: true })}
      <div class="field"><label>المواد الدراسية</label><div id="ms_gs_subjects">جار التحميل...</div></div>
      <div class="field"><label>المعلمون</label><div id="ms_gs_teachers">جار التحميل...</div></div>
      <div class="field"><label>الكورسات</label><div id="ms_gs_courses">جار التحميل...</div></div>
      <div class="grid-2">
        <div class="field"><label>مدة الاشتراك بالأيام (اختياري)</label><input type="number" id="gs_duration_days"></div>
        <div class="field"><label>تاريخ انتهاء محدد (اختياري، بديل عن المدة)</label><input type="datetime-local" id="gs_end_date"></div>
      </div>
      <p class="text-muted" style="font-size:12.5px">لازم تحدد مادة واحدة أو معلم واحد أو كورس واحد على الأقل.</p>`,
    footerHtml: `<button class="btn btn-outline" id="gsCancel">إلغاء</button><button class="btn btn-primary" id="gsSave">منح الاشتراك</button>`,
  });
  wireAsyncSelect("gs_student", fetchAdminStudents);
  const [subjects, teachers, courses] = await Promise.all([getSubjectsCacheFlat(), getTeachersCache(), getCoursesCacheFlat()]);
  document.getElementById("ms_gs_subjects").outerHTML = `<div id="ms_gs_subjects">${multiselectHtml("gs_subjects_box", subjects, [])}</div>`;
  document.getElementById("ms_gs_teachers").outerHTML = `<div id="ms_gs_teachers">${multiselectHtml("gs_teachers_box", teachers, [])}</div>`;
  document.getElementById("ms_gs_courses").outerHTML = `<div id="ms_gs_courses">${multiselectHtml("gs_courses_box", courses, [])}</div>`;

  document.getElementById("gsCancel").onclick = () => Modal.close();
  document.getElementById("gsSave").onclick = async () => {
    const studentId = readAsyncSelect("gs_student");
    if (!studentId) return toast("اختر الطالب", "error");
    const payload = {
      student_id: studentId,
      subject_ids: readMultiselect("gs_subjects_box"),
      teacher_ids: readMultiselect("gs_teachers_box"),
      course_ids: readMultiselect("gs_courses_box"),
      duration_days: Number(document.getElementById("gs_duration_days").value) || undefined,
      end_date: document.getElementById("gs_end_date").value || undefined,
    };
    if (!payload.subject_ids.length && !payload.teacher_ids.length && !payload.course_ids.length) {
      return toast("حدد مادة أو معلم أو كورس واحد على الأقل", "error");
    }
    const btn = document.getElementById("gsSave");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.post(NS, "/admin/subscriptions", payload);
      toast("تم منح الاشتراك بنجاح");
      Modal.close();
      loadSubscriptionsList();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "منح الاشتراك";
    }
  };
}

/* ================= Reschedule Requests ================= */
async function renderRescheduleRequestsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>طلبات تغيير المواعيد</h3>
        <div class="toolbar">
          <select id="rrStatusFilter">
            <option value="pending">معلّقة</option>
            <option value="approved">تمت الموافقة</option>
            <option value="rejected">مرفوضة</option>
            <option value="">الكل</option>
          </select>
        </div>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>المعلم</th><th>الحصة الحالية</th><th>اليوم/الوقت المطلوب</th><th>السبب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
          <tbody id="rrTbody"><tr class="loading-row"><td colspan="6"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("rrStatusFilter").addEventListener("change", loadRescheduleRequests);
  await loadRescheduleRequests();
}

async function loadRescheduleRequests() {
  const tbody = document.getElementById("rrTbody");
  if (!tbody) return;
  const status = document.getElementById("rrStatusFilter")?.value || "";
  tbody.innerHTML = `<tr class="loading-row"><td colspan="6"><span class="spinner spinner-dark"></span></td></tr>`;
  try {
    const res = await api.get(NS, "/admin/reschedule-requests" + qs({ status, limit: 50 }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state">لا توجد طلبات</div></td></tr>`;
      return;
    }
    const statusBadges = { pending: `<span class="badge badge-warning">معلّقة</span>`, approved: `<span class="badge badge-success">تمت الموافقة</span>`, rejected: `<span class="badge badge-danger">مرفوضة</span>` };
    tbody.innerHTML = items
      .map(
        (r) => `<tr>
          <td>${escapeHtml(r.session?.teacher?.name || "-")}</td>
          <td>${escapeHtml(r.session?.code || "-")} — ${escapeHtml(r.session?.day_of_week || "-")} (${escapeHtml(r.session?.start_time || "")}-${escapeHtml(r.session?.end_time || "")})</td>
          <td>${escapeHtml(r.new_day)} (${escapeHtml(r.new_start_time || "")}-${escapeHtml(r.new_end_time || "")})</td>
          <td style="max-width:220px;white-space:normal">${escapeHtml(r.reason)}</td>
          <td>${statusBadges[r.status] || escapeHtml(r.status)}</td>
          <td class="row-actions">
            ${r.status === "pending" ? `<button class="btn btn-success btn-sm" data-act="approve" data-id="${r.id}">موافقة</button><button class="btn btn-danger btn-sm" data-act="reject" data-id="${r.id}">رفض</button>` : "-"}
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=approve]").forEach((b) => (b.onclick = () => reviewRescheduleRequest(Number(b.dataset.id), "approved")));
    tbody.querySelectorAll("[data-act=reject]").forEach((b) => (b.onclick = () => reviewRescheduleRequest(Number(b.dataset.id), "rejected")));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

function reviewRescheduleRequest(id, decision) {
  Modal.open({
    title: decision === "approved" ? "الموافقة على الطلب" : "رفض الطلب",
    bodyHtml: `<div class="field"><label>ملاحظة (اختياري)</label><textarea id="rrNote" rows="2"></textarea></div>`,
    footerHtml: `<button class="btn btn-outline" id="rrCancel">إلغاء</button><button class="btn ${decision === "approved" ? "btn-success" : "btn-danger"}" id="rrConfirm">${decision === "approved" ? "تأكيد الموافقة" : "تأكيد الرفض"}</button>`,
  });
  document.getElementById("rrCancel").onclick = () => Modal.close();
  document.getElementById("rrConfirm").onclick = async () => {
    const btn = document.getElementById("rrConfirm");
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      await api.put(NS, "/admin/reschedule-requests", { id, decision, admin_note: document.getElementById("rrNote").value.trim() });
      toast("تم تحديث الطلب بنجاح");
      Modal.close();
      loadRescheduleRequests();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = decision === "approved" ? "تأكيد الموافقة" : "تأكيد الرفض";
    }
  };
}

/* ================= Exam Attempts (Results & Grading) - reached from an exam row ================= */
function showExamAttemptsPage(exam) {
  currentView = "exam-attempts";
  customPageRefresh = () => renderExamAttemptsPage(document.getElementById("viewRoot"), exam);
  document.querySelectorAll(".nav-item").forEach((n) => n.classList.remove("active"));
  document.getElementById("pageTitle").textContent = `نتائج الامتحان: ${examLabel(exam)}`;
  renderExamAttemptsPage(document.getElementById("viewRoot"), exam);
}

function renderExamAttemptsPage(root, exam) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline btn-sm" id="backToExams">→ رجوع للامتحانات</button>
          <h3 class="mb-0">نتائج الامتحان: ${escapeHtml(examLabel(exam))}</h3>
        </div>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الطالب</th><th>الحالة</th><th>الدرجة</th><th>وقت التسليم</th><th>إجراءات</th></tr></thead>
          <tbody id="eaTbody"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("backToExams").onclick = () => navigate("exams");
  loadExamAttemptsPageData(exam);
}

const ATTEMPT_STATUS_BADGES = {
  in_progress: `<span class="badge badge-muted">جاري الحل</span>`,
  submitted: `<span class="badge badge-warning">بانتظار التصحيح</span>`,
  graded: `<span class="badge badge-success">تم التصحيح</span>`,
};

async function loadExamAttemptsPageData(exam) {
  const tbody = document.getElementById("eaTbody");
  if (!tbody) return;
  try {
    const res = await api.get(NS, "/admin/exams/attempts" + qs({ exam_id: exam.id }));
    const items = extractList(res).items;
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد محاولات لهذا الامتحان بعد</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (a) => `<tr>
          <td>${escapeHtml(a.student?.name || "-")} <span class="text-muted">— ${escapeHtml(a.student?.email || "")}</span></td>
          <td>${ATTEMPT_STATUS_BADGES[a.status] || escapeHtml(a.status)}</td>
          <td>${a.total_score ?? "-"} / ${a.max_score}</td>
          <td>${a.submitted_at ? fmtDate(a.submitted_at) : "-"}</td>
          <td class="row-actions"><button class="btn btn-outline btn-sm" data-act="grade" data-id="${a.id}">${a.status === "submitted" ? "تصحيح" : "عرض"}</button></td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=grade]").forEach((b) => (b.onclick = () => openGradeAttemptModal(Number(b.dataset.id), exam)));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

async function openGradeAttemptModal(attemptId, exam) {
  Modal.open({ title: "تفاصيل المحاولة", size: "modal-lg", bodyHtml: `<div style="text-align:center;padding:20px"><span class="spinner spinner-dark"></span></div>`, footerHtml: "" });
  try {
    const res = await api.get(NS, "/admin/exams/attempts/show" + qs({ id: attemptId }));
    const attempt = res.data || res;
    const answers = attempt.answers || [];
    document.getElementById("modalBody").innerHTML = `
      <div class="field">
        <label>الطالب</label>
        <div style="padding:9px 12px;border:1px solid var(--border);border-radius:8px;background:var(--bg)">${escapeHtml(attempt.student?.name || "-")} — ${escapeHtml(attempt.student?.email || "")}</div>
      </div>
      <div class="field"><label>الدرجة الكلية</label><div>${attempt.total_score ?? "-"} / ${attempt.max_score}</div></div>
      <div id="gaAnswers">
        ${answers
        .map(
          (ans, idx) => `
          <div class="card" style="margin-bottom:10px">
            <div class="card-body">
              <div style="font-weight:700;margin-bottom:6px">${idx + 1}. ${escapeHtml(ans.question_text || "")}</div>
              ${ans.question_type === "mcq"
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
        .join("")}
      </div>`;
    document.getElementById("modalFooter").innerHTML = `<button class="btn btn-outline" id="gaCancel">إغلاق</button><button class="btn btn-primary" id="gaSave">حفظ الدرجات</button>`;
    document.getElementById("gaCancel").onclick = () => Modal.close();
    document.getElementById("gaSave").onclick = async () => {
      const btn = document.getElementById("gaSave");
      const scoreInputs = document.querySelectorAll(".ga-score");
      const gradeAnswers = Array.from(scoreInputs)
        .map((input) => {
          const qId = Number(input.dataset.questionId);
          const noteEl = document.querySelector(`.ga-note[data-question-id="${qId}"]`);
          return { question_id: qId, score_awarded: input.value === "" ? null : Number(input.value), teacher_note: noteEl?.value.trim() || undefined };
        })
        .filter((a) => a.score_awarded !== null);
      try {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner"></span>`;
        await api.put(NS, "/admin/exams/attempts", { attempt_id: attemptId, answers: gradeAnswers });
        toast("تم حفظ الدرجات بنجاح");
        Modal.close();
        loadExamAttemptsPageData(exam);
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

/* ================= Roles & Permissions ================= */
async function renderRolesView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3>الرولز والصلاحيات</h3>
        <button class="btn btn-primary btn-sm" id="addRole">+ إضافة رول</button>
      </div>
      <div class="table-scroll">
        <table>
          <thead><tr><th>الكود</th><th>الاسم (عربي)</th><th>الاسم (إنجليزي)</th><th>عدد الصلاحيات</th><th>إجراءات</th></tr></thead>
          <tbody id="tbody_roles"><tr class="loading-row"><td colspan="5"><span class="spinner spinner-dark"></span></td></tr></tbody>
        </table>
      </div>
    </div>`;
  document.getElementById("addRole").onclick = () => openRoleForm(null);
  await loadRoles();
}

let permissionsCache = null;
async function getPermissions() {
  if (permissionsCache) return permissionsCache;
  const res = await api.get(NS, "/admin/permissions");
  permissionsCache = extractList(res).items.length ? extractList(res).items : (res.data || res);
  if (!Array.isArray(permissionsCache)) permissionsCache = [];
  return permissionsCache;
}

async function loadRoles() {
  const tbody = document.getElementById("tbody_roles");
  try {
    const res = await api.get(NS, "/admin/roles" + qs({ page: 1, limit: 50 }));
    const { items } = extractList(res);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="5"><div class="empty-state">لا توجد رولز</div></td></tr>`;
      return;
    }
    tbody.innerHTML = items
      .map(
        (r) => `<tr>
          <td>${escapeHtml(r.code)}</td>
          <td>${escapeHtml(translatedName(r, "ar") || r.name)}</td>
          <td>${escapeHtml(translatedName(r, "en") || r.name)}</td>
          <td>${(r.permissions || r.permission_ids || []).length}</td>
          <td class="row-actions">
            <button class="btn btn-outline btn-sm" data-act="edit" data-id="${r.id}">تعديل</button>
            <button class="btn btn-danger btn-sm" data-act="del" data-id="${r.id}">حذف</button>
          </td>
        </tr>`
      )
      .join("");
    tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openRoleForm(items.find((i) => String(i.id) === b.dataset.id))));
    tbody.querySelectorAll("[data-act=del]").forEach(
      (b) =>
      (b.onclick = () =>
        confirmDialog("هل تريد حذف هذا الرول؟", async () => {
          await api.del(NS, "/admin/roles", { id: Number(b.dataset.id) });
          toast("تم الحذف بنجاح");
          loadRoles();
        }))
    );
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="5"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

async function openRoleForm(row) {
  const isEdit = !!row;
  const perms = await getPermissions();
  const selected = (row?.permissions || []).map((p) => p.id ?? p);
  Modal.open({
    title: isEdit ? "تعديل رول" : "إضافة رول جديد",
    size: "modal-lg",
    bodyHtml: `
      <div class="grid-2">
        <div class="field"><label>الكود (code) *</label><input type="text" id="r_code" ${isEdit ? "readonly" : ""} value="${escapeHtml(row?.code || "")}" required></div>
        <div class="field"></div>
        <div class="field"><label>الاسم بالعربية *</label><input type="text" id="r_name_ar" required value="${escapeHtml(translatedName(row, "ar") || "")}"></div>
        <div class="field"><label>الاسم بالإنجليزية *</label><input type="text" id="r_name_en" required value="${escapeHtml(translatedName(row, "en") || "")}"></div>
        <div class="field"><label>الوصف بالعربية</label><input type="text" id="r_desc_ar" value="${escapeHtml(row?.translations?.find(t => t.language_code === 'ar')?.description || "")}"></div>
        <div class="field"><label>الوصف بالإنجليزية</label><input type="text" id="r_desc_en" value="${escapeHtml(row?.translations?.find(t => t.language_code === 'en')?.description || "")}"></div>
      </div>
      <div class="field">
        <label>الصلاحيات</label>
        ${multiselectHtml("perm_box", perms.map((p) => ({ id: p.id, label: translatedName(p, "ar") || p.name || p.code })), selected)}
      </div>`,
    footerHtml: `<button class="btn btn-outline" id="rCancel">إلغاء</button><button class="btn btn-primary" id="rSave">${isEdit ? "حفظ" : "إضافة"}</button>`,
  });
  document.getElementById("rCancel").onclick = () => Modal.close();
  document.getElementById("rSave").onclick = async () => {
    const btn = document.getElementById("rSave");
    const payload = {
      name_ar: document.getElementById("r_name_ar").value.trim(),
      name_en: document.getElementById("r_name_en").value.trim(),
      description_ar: document.getElementById("r_desc_ar").value.trim(),
      description_en: document.getElementById("r_desc_en").value.trim(),
      permission_ids: readMultiselect("perm_box"),
    };
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, "/admin/roles", payload);
      } else {
        payload.code = document.getElementById("r_code").value.trim();
        await api.post(NS, "/admin/roles", payload);
      }
      toast(isEdit ? "تم التحديث" : "تمت الإضافة");
      Modal.close();
      loadRoles();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ" : "إضافة";
    }
  };
}

/* ================= Lookups (Cities / Subjects / School Types / Schools / Academic Years / Course Categories / Languages) ================= */
const lookupDefs = [
  {
    key: "cities", label: "المدن", path: "/admin/cities",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "name_ar", label: "الاسم بالعربية", required: true },
      { name: "name_en", label: "الاسم بالإنجليزية", required: true },
    ],
  },
  {
    key: "subjects", label: "المواد الدراسية", path: "/admin/subjects",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "name_ar", label: "الاسم بالعربية", required: true },
      { name: "name_en", label: "الاسم بالإنجليزية", required: true },
      { name: "description_ar", label: "الوصف بالعربية", type: "textarea" },
      { name: "description_en", label: "الوصف بالإنجليزية", type: "textarea" },
      {
        name: "academic_year_ids", label: `السنوات الدراسية المرتبطة <span class="badge-new">جديد</span>`, type: "multiselect",
        fetchAll: getAcademicYearsCache,
        currentFrom: (row) => (row?.academic_years || []).map((y) => y.id),
        render: (row) => (row.academic_years || []).map((y) => translatedName(y, "ar")).join("، ") || "-",
      },
    ],
  },
  {
    key: "school-types", label: "أنواع المدارس", path: "/admin/school-types",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "name_ar", label: "الاسم بالعربية", required: true },
      { name: "name_en", label: "الاسم بالإنجليزية", required: true },
    ],
  },
  {
    key: "schools", label: "المدارس", path: "/admin/schools",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "name_ar", label: "الاسم بالعربية", required: true },
      { name: "name_en", label: "الاسم بالإنجليزية", required: true },
      { name: "school_type_id", label: "نوع المدرسة", type: "asyncSelect", fetch: fetchSchoolTypes, resolve: resolveSchoolTypeLabel },
      { name: "city_id", label: "المدينة", type: "asyncSelect", fetch: fetchCities, resolve: resolveCityLabel },
    ],
  },
  {
    key: "academic-years", label: "السنوات الدراسية", path: "/admin/academic-years",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "number", label: "الرقم", type: "number" },
      { name: "name_ar", label: "الاسم بالعربية", required: true },
      { name: "name_en", label: "الاسم بالإنجليزية", required: true },
    ],
  },
  {
    key: "course-categories", label: "أقسام الكورسات", path: "/admin/course-categories",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "name_ar", label: "الاسم بالعربية", required: true },
      { name: "name_en", label: "الاسم بالإنجليزية", required: true },
      { name: "image", label: "رابط صورة (اختياري)" },
    ],
  },
  {
    key: "languages", label: "اللغات", path: "/admin/languages",
    fields: [
      { name: "code", label: "الكود", required: true },
      { name: "name", label: "الاسم", required: true },
      { name: "direction", label: "الاتجاه (ltr/rtl)" },
      { name: "is_default", label: "لغة افتراضية؟ (0/1)", type: "number" },
    ],
  },
];

let lookupActiveKey = lookupDefs[0].key;

function renderLookupsView(root) {
  root.innerHTML = `
    <div class="card">
      <div class="subtabs" id="lookupTabs">
        ${lookupDefs.map((d) => `<button data-key="${d.key}" class="${d.key === lookupActiveKey ? "active" : ""}">${d.label}</button>`).join("")}
      </div>
      <div id="lookupBody"></div>
    </div>`;
  document.querySelectorAll("#lookupTabs button").forEach(
    (b) =>
    (b.onclick = () => {
      lookupActiveKey = b.dataset.key;
      document.querySelectorAll("#lookupTabs button").forEach((x) => x.classList.toggle("active", x === b));
      renderLookupTable();
    })
  );
  renderLookupTable();
}

function renderLookupTable() {
  const def = lookupDefs.find((d) => d.key === lookupActiveKey);
  const body = document.getElementById("lookupBody");
  const st = ensureState("lk_" + def.key);
  body.innerHTML = `
    <div class="card-header">
      <div class="toolbar"><input type="search" id="lkSearch" placeholder="بحث..." value="${escapeHtml(st.search)}"></div>
      <button class="btn btn-primary btn-sm" id="lkAdd">+ إضافة</button>
    </div>
    <div id="lkStats"></div>
    <div class="table-scroll">
      <table>
        <thead><tr>${def.fields.map((f) => `<th>${f.label}</th>`).join("")}<th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody id="lkTbody"><tr class="loading-row"><td colspan="${def.fields.length + 2}"><span class="spinner spinner-dark"></span></td></tr></tbody>
      </table>
    </div>
    <div id="lkPg"></div>`;
  document.getElementById("lkAdd").onclick = () => openLookupForm(def, null);
  let debounce;
  document.getElementById("lkSearch").addEventListener("input", (e) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      st.search = e.target.value;
      st.page = 1;
      loadLookupList(def);
    }, 400);
  });
  loadLookupList(def);
}

async function loadLookupList(def) {
  const st = ensureState("lk_" + def.key);
  const tbody = document.getElementById("lkTbody");
  try {
    const res = await api.get(NS, def.path + qs({ page: st.page, limit: st.perPage, search: st.search }));
    const { items, page, totalPages, total, statistics } = extractList(res);
    st.page = page || st.page;
    document.getElementById("lkStats").innerHTML = statsBarHtml(statistics);
    if (!items.length) {
      tbody.innerHTML = `<tr><td colspan="${def.fields.length + 2}"><div class="empty-state">لا توجد بيانات</div></td></tr>`;
    } else {
      tbody.innerHTML = items
        .map(
          (row) => `<tr>
            ${def.fields.map((f) => `<td>${f.render ? f.render(row) : escapeHtml(row[f.name] ?? "-")}</td>`).join("")}
            <td>${statusBadge(row.is_active != 0)}</td>
            <td class="row-actions">
              <button class="btn btn-outline btn-sm" data-act="edit" data-id="${row.id}">تعديل</button>
              <button class="btn btn-danger btn-sm" data-act="del" data-id="${row.id}">حذف</button>
            </td>
          </tr>`
        )
        .join("");
      tbody.querySelectorAll("[data-act=edit]").forEach((b) => (b.onclick = () => openLookupForm(def, items.find((i) => String(i.id) === b.dataset.id))));
      tbody.querySelectorAll("[data-act=del]").forEach(
        (b) =>
        (b.onclick = () =>
          confirmDialog("هل تريد حذف هذا العنصر؟", async () => {
            await api.del(NS, def.path, { id: Number(b.dataset.id) });
            toast("تم الحذف بنجاح");
            loadLookupList(def);
          }))
      );
    }
    const pgEl = document.getElementById("lkPg");
    pgEl.innerHTML = paginationHtml(st.page, totalPages, total, st.perPage);
    wirePagination(pgEl, st, () => loadLookupList(def));
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="${def.fields.length + 2}"><div class="error-box show">${escapeHtml(err.message)}</div></td></tr>`;
  }
}

function openLookupForm(def, row) {
  const isEdit = !!row;
  Modal.open({
    title: (isEdit ? "تعديل - " : "إضافة - ") + def.label,
    bodyHtml: `
      ${def.fields
        .map((f) => {
          if (f.type === "asyncSelect") {
            return asyncSelectHtml(`lf_${f.name}`, {
              label: f.label,
              required: f.required,
              currentId: row?.[f.name] ?? "",
              currentLabel: row?.[f.name + "_label"] || "",
            });
          }
          if (f.type === "multiselect") {
            return `<div class="field"><label>${f.label}</label><div id="lf_${f.name}_wrap">جار التحميل...</div></div>`;
          }
          return `<div class="field">
            <label>${f.label}${f.required ? " *" : ""}</label>
            ${f.type === "textarea"
              ? `<textarea id="lf_${f.name}" rows="2">${escapeHtml(row?.[f.name] || "")}</textarea>`
              : `<input type="${f.type === "number" ? "number" : "text"}" id="lf_${f.name}" ${f.required ? "required" : ""} value="${escapeHtml(row?.[f.name] ?? "")}">`
            }
          </div>`;
        })
        .join("")}
      <div class="field"><label>مفعّل؟</label><select id="lf_is_active"><option value="1" ${row?.is_active != 0 ? "selected" : ""}>نعم</option><option value="0" ${row?.is_active == 0 ? "selected" : ""}>لا</option></select></div>`,
    footerHtml: `<button class="btn btn-outline" id="lfCancel">إلغاء</button><button class="btn btn-primary" id="lfSave">${isEdit ? "حفظ" : "إضافة"}</button>`,
  });
  def.fields.forEach(async (f) => {
    if (f.type === "asyncSelect") {
      wireAsyncSelect(`lf_${f.name}`, f.fetch, { resolveLabel: f.resolve });
    } else if (f.type === "multiselect") {
      const options = await f.fetchAll();
      const selected = f.currentFrom ? f.currentFrom(row) : [];
      document.getElementById(`lf_${f.name}_wrap`).innerHTML = multiselectHtml(`lf_${f.name}_box`, options, selected);
    }
  });
  document.getElementById("lfCancel").onclick = () => Modal.close();
  document.getElementById("lfSave").onclick = async () => {
    const btn = document.getElementById("lfSave");
    const payload = {};
    def.fields.forEach((f) => {
      if (f.type === "asyncSelect") {
        payload[f.name] = readAsyncSelect(`lf_${f.name}`);
        return;
      }
      if (f.type === "multiselect") {
        payload[f.name] = readMultiselect(`lf_${f.name}_box`);
        return;
      }
      const v = document.getElementById(`lf_${f.name}`).value;
      payload[f.name] = f.type === "number" ? Number(v) || undefined : v.trim();
    });
    payload.is_active = Number(document.getElementById("lf_is_active").value);
    try {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner"></span>`;
      if (isEdit) {
        payload.id = row.id;
        await api.put(NS, def.path, payload);
      } else {
        await api.post(NS, def.path, payload);
      }
      toast(isEdit ? "تم التحديث" : "تمت الإضافة");
      Modal.close();
      loadLookupList(def);
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = isEdit ? "حفظ" : "إضافة";
    }
  };
}

async function renderSiteSettingsView(root) {
  root.innerHTML = `<div class="card" style="padding:20px"><div style="text-align:center;padding:40px"><span class="spinner spinner-dark"></span></div></div>`;
  try {
    const res = await api.get(NS, "/admin/site-settings");
    const s = res.data || res;

    root.innerHTML = `
      <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:12px">
          <div>
            <h3>🌐 التحكم بالصفحة الرئيسية للموقع (Landing Page Settings)</h3>
            <p style="color:var(--text-muted);font-size:13px;margin-top:4px">تعديل النصوص والإحصائيات وأزرار الهيدر والفوتر وإدارة البانرات والظهور بالموقع</p>
          </div>
          <button class="btn btn-primary" id="saveSiteSettingsBtn">💾 حفظ التغيرات</button>
        </div>
        
        <div class="subtabs" id="siteSettingsTabs">
          <button data-tab="hero" class="active">🎯 قسم الـ Hero والترويج</button>
          <button data-tab="stats">📊 الإحصائيات والأرقام</button>
          <button data-tab="about">ℹ️ صفحة من نحن (About Us)</button>
          <button data-tab="banners">🖼️ البانرات الإعلانية</button>
          <button data-tab="visibility">📚 الكورسات والكتب المظهرة</button>
          <button data-tab="footer">🔗 الفوتر ووسائل التواصل</button>
        </div>

        <div class="card-body">
          <form id="siteSettingsForm">
            <div class="tab-pane active" id="tab_hero">
              <h4 style="margin-bottom:15px;color:var(--primary)">🎯 نصوص وأزرار واجهة الهيدر (Hero Section)</h4>
              <div class="grid-2">
                <div class="field">
                  <label>الشارة الترويجية (عربي)</label>
                  <input type="text" name="hero_badge_text_ar" value="${escapeHtml(s.hero_badge_text_ar || "")}">
                </div>
                <div class="field">
                  <label>الشارة الترويجية (إنجليزي)</label>
                  <input type="text" name="hero_badge_text_en" value="${escapeHtml(s.hero_badge_text_en || "")}">
                </div>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label>العنوان الرئيسي (عربي) *</label>
                  <input type="text" name="hero_title_ar" required value="${escapeHtml(s.hero_title_ar || "")}">
                </div>
                <div class="field">
                  <label>العنوان الرئيسي (إنجليزي) *</label>
                  <input type="text" name="hero_title_en" required value="${escapeHtml(s.hero_title_en || "")}">
                </div>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label>الوصف الفرعي (عربي)</label>
                  <textarea name="hero_subtitle_ar" rows="3">${escapeHtml(s.hero_subtitle_ar || "")}</textarea>
                </div>
                <div class="field">
                  <label>الوصف الفرعي (إنجليزي)</label>
                  <textarea name="hero_subtitle_en" rows="3">${escapeHtml(s.hero_subtitle_en || "")}</textarea>
                </div>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label>نص الزر الرئيسي (عربي)</label>
                  <input type="text" name="hero_primary_btn_text_ar" value="${escapeHtml(s.hero_primary_btn_text_ar || "")}">
                </div>
                <div class="field">
                  <label>نص الزر الرئيسي (إنجليزي)</label>
                  <input type="text" name="hero_primary_btn_text_en" value="${escapeHtml(s.hero_primary_btn_text_en || "")}">
                </div>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label>رابط الزر الرئيسي</label>
                  <input type="text" name="hero_primary_btn_link" value="${escapeHtml(s.hero_primary_btn_link || "/courses")}">
                </div>
                <div class="field">
                  <label>رابط الزر الفرعي</label>
                  <input type="text" name="hero_secondary_btn_link" value="${escapeHtml(s.hero_secondary_btn_link || "#plans")}">
                </div>
              </div>
              <div class="grid-3" style="margin-top:15px">
                <div class="field">
                  <label>نسبة نجاح الطلاب</label>
                  <input type="text" name="hero_success_rate" value="${escapeHtml(s.hero_success_rate || "94%")}">
                </div>
                <div class="field">
                  <label>عدد الحصص المباشرة</label>
                  <input type="text" name="hero_live_sessions_count" value="${escapeHtml(s.hero_live_sessions_count || "+1200")}">
                </div>
                <div class="field">
                  <label>تقييم المنصة</label>
                  <input type="text" name="hero_rating" value="${escapeHtml(s.hero_rating || "4.9/5")}">
                </div>
              </div>
            </div>

            <div class="tab-pane" id="tab_stats" style="display:none">
              <h4 style="margin-bottom:15px;color:var(--primary)">📊 شريط الإحصائيات والأرقام (Stats Bar)</h4>
              <div class="grid-2">
                <div class="field"><label>عدد الكورسات</label><input type="text" name="stats_courses_count" value="${escapeHtml(s.stats_courses_count || "+300")}"></div>
                <div class="field"><label>تسمية الكورسات (عربي/إنجليزي)</label><input type="text" name="stats_courses_label_ar" value="${escapeHtml(s.stats_courses_label_ar || "كورس تعليمي")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>عدد الكتب والمذكرات</label><input type="text" name="stats_books_count" value="${escapeHtml(s.stats_books_count || "+120")}"></div>
                <div class="field"><label>تسمية الكتب (عربي/إنجليزي)</label><input type="text" name="stats_books_label_ar" value="${escapeHtml(s.stats_books_label_ar || "كتاب ومذكرة")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>عدد المعلمين</label><input type="text" name="stats_teachers_count" value="${escapeHtml(s.stats_teachers_count || "+85")}"></div>
                <div class="field"><label>تسمية المعلمين (عربي/إنجليزي)</label><input type="text" name="stats_teachers_label_ar" value="${escapeHtml(s.stats_teachers_label_ar || "مدرّس متخصص")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>عدد الطلاب</label><input type="text" name="stats_students_count" value="${escapeHtml(s.stats_students_count || "+40k")}"></div>
                <div class="field"><label>تسمية الطلاب (عربي/إنجليزي)</label><input type="text" name="stats_students_label_ar" value="${escapeHtml(s.stats_students_label_ar || "طالب مسجل")}"></div>
              </div>
            </div>

            <div class="tab-pane" id="tab_about" style="display:none">
              <h4 style="margin-bottom:15px;color:var(--primary)">ℹ️ التحكم الكامل في محتوى صفحة "من نحن" (About Us)</h4>
              
              <div class="grid-2">
                <div class="field"><label>عنوان الهيدر (عربي)</label><input type="text" name="about_title_ar" value="${escapeHtml(s.about_title_ar || "من نحن")}"></div>
                <div class="field"><label>عنوان الهيدر (إنجليزي)</label><input type="text" name="about_title_en" value="${escapeHtml(s.about_title_en || "About Us")}"></div>
              </div>

              <div class="grid-2">
                <div class="field"><label>الوصف التعريفي (عربي)</label><textarea name="about_description_ar" rows="3">${escapeHtml(s.about_description_ar || "")}</textarea></div>
                <div class="field"><label>الوصف التعريفي (إنجليزي)</label><textarea name="about_description_en" rows="3">${escapeHtml(s.about_description_en || "")}</textarea></div>
              </div>

              <h5 style="margin:15px 0 10px;color:var(--primary)">📊 أرقام وإحصائيات صفحة من نحن</h5>
              <div class="grid-3">
                <div class="field"><label>عدد الكورسات</label><input type="text" name="about_stats_courses" value="${escapeHtml(s.about_stats_courses || "150+")}"></div>
                <div class="field"><label>عدد الكتب والكتب المطبوعة</label><input type="text" name="about_stats_books" value="${escapeHtml(s.about_stats_books || "40+")}"></div>
                <div class="field"><label>عدد الطلاب المسجلين</label><input type="text" name="about_stats_students" value="${escapeHtml(s.about_stats_students || "20,000+")}"></div>
              </div>

              <h5 style="margin:15px 0 10px;color:var(--primary)">🎯 عنوان ونص قسم "رسالتنا"</h5>
              <div class="grid-2">
                <div class="field"><label>عنوان الرسالة (عربي)</label><input type="text" name="about_mission_title_ar" value="${escapeHtml(s.about_mission_title_ar || "رسالتنا")}"></div>
                <div class="field"><label>عنوان الرسالة (إنجليزي)</label><input type="text" name="about_mission_title_en" value="${escapeHtml(s.about_mission_title_en || "Our Mission")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>نص الرسالة (عربي)</label><textarea name="about_mission_text_ar" rows="3">${escapeHtml(s.about_mission_text_ar || "")}</textarea></div>
                <div class="field"><label>نص الرسالة (إنجليزي)</label><textarea name="about_mission_text_en" rows="3">${escapeHtml(s.about_mission_text_en || "")}</textarea></div>
              </div>

              <h5 style="margin:15px 0 10px;color:var(--primary)">✨ قسم "ماذا نقدم" (الخدمات الـ 3)</h5>
              <div class="grid-2">
                <div class="field"><label>العنوان 1 (عربي/إنجليزي)</label><input type="text" name="about_offering1_title_ar" value="${escapeHtml(s.about_offering1_title_ar || "كورسات مباشرة")}"></div>
                <div class="field"><label>الوصف 1 (عربي)</label><input type="text" name="about_offering1_desc_ar" value="${escapeHtml(s.about_offering1_desc_ar || "")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>العنوان 2 (عربي/إنجليزي)</label><input type="text" name="about_offering2_title_ar" value="${escapeHtml(s.about_offering2_title_ar || "كورسات مسجلة")}"></div>
                <div class="field"><label>الوصف 2 (عربي)</label><input type="text" name="about_offering2_desc_ar" value="${escapeHtml(s.about_offering2_desc_ar || "")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>العنوان 3 (عربي/إنجليزي)</label><input type="text" name="about_offering3_title_ar" value="${escapeHtml(s.about_offering3_title_ar || "كتب تعليمية")}"></div>
                <div class="field"><label>الوصف 3 (عربي)</label><input type="text" name="about_offering3_desc_ar" value="${escapeHtml(s.about_offering3_desc_ar || "")}"></div>
              </div>

              <h5 style="margin:15px 0 10px;color:var(--primary)">💎 قسم "قيمنا" (القيم الـ 4)</h5>
              <div class="grid-2">
                <div class="field"><label>القيمة 1: العنوان (عربي)</label><input type="text" name="about_val1_title_ar" value="${escapeHtml(s.about_val1_title_ar || "الجودة أولاً")}"></div>
                <div class="field"><label>القيمة 1: الوصف (عربي)</label><input type="text" name="about_val1_desc_ar" value="${escapeHtml(s.about_val1_desc_ar || "")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>القيمة 2: العنوان (عربي)</label><input type="text" name="about_val2_title_ar" value="${escapeHtml(s.about_val2_title_ar || "مرونة كاملة")}"></div>
                <div class="field"><label>القيمة 2: الوصف (عربي)</label><input type="text" name="about_val2_desc_ar" value="${escapeHtml(s.about_val2_desc_ar || "")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>القيمة 3: العنوان (عربي)</label><input type="text" name="about_val3_title_ar" value="${escapeHtml(s.about_val3_title_ar || "مجتمع داعم")}"></div>
                <div class="field"><label>القيمة 3: الوصف (عربي)</label><input type="text" name="about_val3_desc_ar" value="${escapeHtml(s.about_val3_desc_ar || "")}"></div>
              </div>
              <div class="grid-2">
                <div class="field"><label>القيمة 4: العنوان (عربي)</label><input type="text" name="about_val4_title_ar" value="${escapeHtml(s.about_val4_title_ar || "شهادات معتمدة")}"></div>
                <div class="field"><label>القيمة 4: الوصف (عربي)</label><input type="text" name="about_val4_desc_ar" value="${escapeHtml(s.about_val4_desc_ar || "")}"></div>
              </div>
            </div>

            <div class="tab-pane" id="tab_banners" style="display:none">
              <div class="card-header" style="padding:0 0 15px">
                <h4 style="color:var(--primary)">🖼️ إدارة البانرات الإعلانية في الصفحة الرئيسية</h4>
                <button type="button" class="btn btn-outline btn-sm" id="btnManageBanners">إدارة البانرات بالكامل 🔗</button>
              </div>
            </div>

            <div class="tab-pane" id="tab_visibility" style="display:none">
              <h4 style="margin-bottom:15px;color:var(--primary)">📚 التحكم في ظهور الكورسات والكتب على الصفحة الرئيسية للموقع</h4>
              <p style="color:var(--text-muted);font-size:13px;margin-bottom:20px">الكورسات والكتب التي تم التحديد عليها بـ (ظاهر) ستظهر مباشرة لزوار الويب سايت في قسم "أحدث الكورسات" وقسم "المكتبة".</p>
              
              <div class="grid-2" style="gap:25px">
                <div>
                  <h5>📚 الكورسات المتاحة للموقع:</h5>
                  <div id="siteCoursesTable">جار التحميل...</div>
                </div>
                <div>
                  <h5>📖 الكتب والمذكرات المتاحة للموقع:</h5>
                  <div id="siteBooksTable">جار التحميل...</div>
                </div>
              </div>
            </div>

            <div class="tab-pane" id="tab_footer" style="display:none">
              <h4 style="margin-bottom:15px;color:var(--primary)">🔗 الفوتر ووسائل التواصل الاجتماعي</h4>
              <div class="grid-2">
                <div class="field"><label>وصف المنصة في الفوتر (عربي)</label><textarea name="footer_description_ar" rows="3">${escapeHtml(s.footer_description_ar || "")}</textarea></div>
                <div class="field"><label>وصف المنصة في الفوتر (إنجليزي)</label><textarea name="footer_description_en" rows="3">${escapeHtml(s.footer_description_en || "")}</textarea></div>
              </div>
              <div class="grid-3">
                <div class="field"><label>رابط الفيسبوك</label><input type="text" name="facebook_link" value="${escapeHtml(s.facebook_link || "")}"></div>
                <div class="field"><label>رابط الإنستجرام</label><input type="text" name="instagram_link" value="${escapeHtml(s.instagram_link || "")}"></div>
                <div class="field"><label>رابط اليوتيوب</label><input type="text" name="youtube_link" value="${escapeHtml(s.youtube_link || "")}"></div>
              </div>
            </div>
          </form>
        </div>
      </div>
    `;

    document.querySelectorAll("#siteSettingsTabs button").forEach((btn) => {
      btn.onclick = () => {
        document.querySelectorAll("#siteSettingsTabs button").forEach((x) => x.classList.remove("active"));
        btn.classList.add("active");
        const tabKey = btn.dataset.tab;
        document.querySelectorAll("#siteSettingsForm .tab-pane").forEach((pane) => {
          pane.style.display = pane.id === `tab_${tabKey}` ? "block" : "none";
        });
        if (tabKey === "visibility") loadSiteVisibilityTables();
      };
    });

    document.getElementById("saveSiteSettingsBtn").onclick = async () => {
      const form = document.getElementById("siteSettingsForm");
      const formData = new FormData(form);
      const payload = {};
      formData.forEach((val, key) => {
        payload[key] = val.trim();
      });
      const btn = document.getElementById("saveSiteSettingsBtn");
      try {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner"></span> جاري الحفظ...`;
        await api.post(NS, "/admin/site-settings", payload);
        toast("تم حفظ إعدادات الموقع بنجاح!");
      } catch (err) {
        toast(err.message || "فشل حفظ الإعدادات", "error");
      } finally {
        btn.disabled = false;
        btn.innerHTML = `💾 حفظ التغيرات`;
      }
    };

    document.getElementById("btnManageBanners").onclick = () => navigate("banners");

  } catch (err) {
    root.innerHTML = `<div class="card"><div class="card-body error-box show">${escapeHtml(err.message)}</div></div>`;
  }
}

async function loadSiteVisibilityTables() {
  const cEl = document.getElementById("siteCoursesTable");
  const bEl = document.getElementById("siteBooksTable");
  try {
    const cRes = await api.get(NS, "/admin/courses?limit=50");
    const cList = extractList(cRes).items;
    cEl.innerHTML = `
      <table class="table" style="font-size:13px;width:100%">
        <thead><tr><th>الكورس</th><th>الحالة بالموقع</th></tr></thead>
        <tbody>
          ${cList.map(c => `
            <tr>
              <td>${escapeHtml(c.name_ar || c.name || '')}</td>
              <td>
                <button type="button" class="btn ${c.show_on_website != 0 ? 'btn-success' : 'btn-outline'} btn-sm" data-cid="${c.id}">
                  ${c.show_on_website != 0 ? '🌐 ظاهر بالموقع' : '🙈 مخفي'}
                </button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    cEl.querySelectorAll("[data-cid]").forEach(btn => {
      btn.onclick = async () => {
        const id = Number(btn.dataset.cid);
        await api.post(NS, "/admin/courses/toggle-website-visibility", { id });
        toast("تم تحديث حالة الظهور للكورس");
        loadSiteVisibilityTables();
      };
    });

    const bRes = await api.get(NS, "/admin/books?limit=50");
    const bList = extractList(bRes).items;
    bEl.innerHTML = `
      <table class="table" style="font-size:13px;width:100%">
        <thead><tr><th>الكتاب</th><th>الحالة بالموقع</th></tr></thead>
        <tbody>
          ${bList.map(b => `
            <tr>
              <td>${escapeHtml(b.title_ar || b.title || '')}</td>
              <td>
                <button type="button" class="btn ${b.show_on_website != 0 ? 'btn-success' : 'btn-outline'} btn-sm" data-bid="${b.id}">
                  ${b.show_on_website != 0 ? '🌐 ظاهر بالموقع' : '🙈 مخفي'}
                </button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
    bEl.querySelectorAll("[data-bid]").forEach(btn => {
      btn.onclick = async () => {
        const id = Number(btn.dataset.bid);
        await api.post(NS, "/admin/books/toggle-website-visibility", { id });
        toast("تم تحديث حالة الظهور للكتاب");
        loadSiteVisibilityTables();
      };
    });

  } catch (err) {
    cEl.innerHTML = `<div class="error-box show">${escapeHtml(err.message)}</div>`;
  }
}

/* ---------- boot ---------- */
(function init() {
  const savedLang = localStorage.getItem("najeh_lang") || "ar";
  document.getElementById("langSwitch").value = savedLang;
  applyLangDirection(savedLang);
  if (isLoggedIn()) showApp();
  else showLogin();
})();
