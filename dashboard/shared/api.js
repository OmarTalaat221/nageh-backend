/* ==== Najeh Dashboard - Shared API client ==== */
const API_BASE = "https://camp-coding.online/najeh";

const Store = {
  get(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
  set(k, v) { localStorage.setItem(k, JSON.stringify(v)); },
  remove(k) { localStorage.removeItem(k); },
};

function getTokens(ns) {
  return Store.get(ns + "_tokens") || {};
}
function setTokens(ns, tokens) {
  Store.set(ns + "_tokens", tokens);
}
function clearTokens(ns) {
  Store.remove(ns + "_tokens");
  Store.remove(ns + "_user");
}

/**
 * Generic API request helper.
 * ns: "admin" or "teacher" - used to scope token storage & refresh endpoint.
 */
async function apiRequest(ns, path, { method = "GET", body = null, auth = true, isRetry = false } = {}) {
  const isFormData = typeof FormData !== "undefined" && body instanceof FormData;
  const headers = {
    "Accept-Language": localStorage.getItem("najeh_lang") || "ar",
  };
  // Leave Content-Type unset for FormData - the browser sets it (with the multipart boundary) itself.
  if (!isFormData) headers["Content-Type"] = "application/json";
  const tokens = getTokens(ns);
  if (auth && tokens.token) {
    headers["Authorization"] = "Bearer " + tokens.token;
  }

  let res, data;
  try {
    res = await fetch(API_BASE + path, {
      method,
      headers,
      body: body ? (isFormData ? body : JSON.stringify(body)) : undefined,
    });
  } catch (netErr) {
    throw { message: "تعذر الاتصال بالخادم. تأكد من الإنترنت.", raw: netErr };
  }

  let parseFailed = false;
  const rawText = await res.text();
  try {
    data = JSON.parse(rawText);
  } catch (e) {
    parseFailed = true;
    data = { status: "error", message: "استجابة غير صالحة من الخادم (الرد لم يكن JSON صحيح)" };
    // The PHP backend prints warnings/notices as raw HTML before the JSON on certain errors,
    // which breaks JSON.parse - log the raw body so it's visible while debugging.
    console.error(`[najeh api] non-JSON response from ${path}:`, rawText.slice(0, 800));
  }

  if (res.status === 401 && auth && tokens.refresh_token && !isRetry) {
    const refreshed = await tryRefresh(ns);
    if (refreshed) {
      return apiRequest(ns, path, { method, body, auth, isRetry: true });
    }
    clearTokens(ns);
    window.dispatchEvent(new CustomEvent("najeh:unauthorized"));
    throw { message: data.message || "انتهت الجلسة، برجاء تسجيل الدخول مرة أخرى", status: 401 };
  }

  // Require an explicit "success" status rather than merely the absence of "error" - a response
  // that fails to parse, or omits `status` entirely, must never be treated as a success.
  if (!res.ok || parseFailed || data.status !== "success") {
    throw { message: data.message || "حدث خطأ غير متوقع", status: res.status, data };
  }

  return data;
}

async function tryRefresh(ns) {
  const tokens = getTokens(ns);
  if (!tokens.refresh_token) return false;
  try {
    const refreshPaths = { admin: "/admin/auth/refresh", teacher: "/teacher/auth/refresh", student: "/user/student/auth/refresh" };
    const path = refreshPaths[ns] || "/" + ns + "/auth/refresh";
    const res = await fetch(API_BASE + path, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ refresh_token: tokens.refresh_token }),
    });
    const data = await res.json();
    if (!res.ok) return false;
    const newTokens = data.data || data;
    setTokens(ns, {
      token: newTokens.token || newTokens.access_token,
      refresh_token: newTokens.refresh_token || tokens.refresh_token,
    });
    return true;
  } catch (e) {
    return false;
  }
}

const api = {
  get: (ns, path, auth = true) => apiRequest(ns, path, { method: "GET", auth }),
  post: (ns, path, body, auth = true) => apiRequest(ns, path, { method: "POST", body, auth }),
  put: (ns, path, body, auth = true) => apiRequest(ns, path, { method: "PUT", body, auth }),
  del: (ns, path, body, auth = true) => apiRequest(ns, path, { method: "DELETE", body, auth }),
};

/* ==== Helpers ==== */
function qs(params) {
  const usp = new URLSearchParams();
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== "") usp.set(k, v);
  });
  const s = usp.toString();
  return s ? "?" + s : "";
}

function translatedName(entity, lang) {
  if (!entity) return "";
  lang = lang || localStorage.getItem("najeh_lang") || "ar";
  if (entity["name_" + lang]) return entity["name_" + lang];
  if (entity.name) return entity.name;
  if (entity.translations) {
    const t = entity.translations.find((x) => (x.language_code || x.language) === lang) || entity.translations[0];
    return t ? t.name : "";
  }
  return entity.name_ar || entity.name_en || "";
}

function escapeHtml(str) {
  if (str === null || str === undefined) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function fmtDate(d) {
  if (!d) return "-";
  try {
    return new Date(d).toLocaleString("ar-EG", { dateStyle: "medium", timeStyle: "short" });
  } catch (e) {
    return d;
  }
}

/** Convert a File to base64 data URI (used as "upload" option for image fields). */
function fileToDataUri(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

/** Toast notifications */
function toast(msg, type = "success") {
  const el = document.getElementById("toastArea");
  if (!el) { alert(msg); return; }
  const div = document.createElement("div");
  div.className = `toast-msg toast-${type}`;
  div.textContent = msg;
  el.appendChild(div);
  requestAnimationFrame(() => div.classList.add("show"));
  setTimeout(() => {
    div.classList.remove("show");
    setTimeout(() => div.remove(), 300);
  }, 3500);
}
