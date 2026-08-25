/* ==== Najeh Dashboard - Small UI kit: modal, image field, multiselect, pagination, confirm ==== */

const Modal = {
  open({ title, bodyHtml, footerHtml, size = "" }) {
    const backdrop = document.getElementById("modalBackdrop");
    const box = document.getElementById("modalBox");
    document.getElementById("modalTitle").textContent = title;
    document.getElementById("modalBody").innerHTML = bodyHtml;
    document.getElementById("modalFooter").innerHTML = footerHtml || "";
    box.className = "modal-box" + (size ? " " + size : "");
    backdrop.classList.add("show");
  },
  close() {
    document.getElementById("modalBackdrop").classList.remove("show");
  },
};

document.addEventListener("click", (e) => {
  if (e.target.id === "modalBackdrop" || e.target.id === "modalClose") Modal.close();
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") Modal.close();
});

/* ==== Async select: a searchable, infinite-scroll dropdown for any "pick one by id" field ====
 * Replaces raw "type the numeric ID" inputs (city_id, school_id, role_id, ...) with a proper
 * select that fetches real records from the API, paginated, loading more as you scroll down.
 */
document.addEventListener("click", (e) => {
  document.querySelectorAll(".aselect-dropdown.show").forEach((dd) => {
    const ctrl = document.getElementById(dd.id.replace(/_dd$/, "_ctrl"));
    if (!dd.contains(e.target) && ctrl && !ctrl.contains(e.target)) dd.classList.remove("show");
  });
});

function asyncSelectHtml(id, { label, currentId = "", currentLabel = "", placeholder = "اختر...", required = false } = {}) {
  return `
    <div class="field">
      ${label ? `<label>${label}${required ? " *" : ""}</label>` : ""}
      <div class="aselect" id="${id}_wrap">
        <div class="aselect-ctrl" id="${id}_ctrl" tabindex="0">
          <span class="aselect-value" id="${id}_label">${currentLabel ? escapeHtml(currentLabel) : `<span class="text-muted">${placeholder}</span>`}</span>
          <span class="aselect-arrow">▾</span>
        </div>
        <input type="hidden" id="${id}_value" value="${currentId}">
        <div class="aselect-dropdown" id="${id}_dd">
          <input type="search" id="${id}_search" placeholder="بحث...">
          <div class="aselect-list" id="${id}_list"></div>
          <div class="aselect-loading" id="${id}_loading"><span class="spinner spinner-dark"></span></div>
        </div>
      </div>
    </div>`;
}

/**
 * Wires an async-select field. `fetchPage(page, search)` must resolve to `{ items, hasMore }`
 * where each item is `{ id, label }`. `resolveLabel(id)` (optional) resolves the display label
 * for a pre-filled id when the caller only knows the id, not its label (e.g. editing a record
 * whose list endpoint didn't include the related entity's translated name).
 */
function wireAsyncSelect(id, fetchPage, { resolveLabel } = {}) {
  const ctrl = document.getElementById(id + "_ctrl");
  const dd = document.getElementById(id + "_dd");
  const list = document.getElementById(id + "_list");
  const loading = document.getElementById(id + "_loading");
  const search = document.getElementById(id + "_search");
  const valueInput = document.getElementById(id + "_value");
  const labelEl = document.getElementById(id + "_label");
  if (!ctrl) return;

  let page = 1, hasMore = true, loadingNow = false, term = "", loadedOnce = false;

  async function loadMore(reset) {
    if (loadingNow || (!hasMore && !reset)) return;
    if (reset) { page = 1; hasMore = true; list.innerHTML = ""; }
    loadingNow = true;
    loading.classList.add("show");
    try {
      const { items, hasMore: more } = await fetchPage(page, term);
      items.forEach((item) => {
        const opt = document.createElement("div");
        opt.className = "aselect-option" + (String(item.id) === valueInput.value ? " selected" : "");
        opt.dataset.id = item.id;
        opt.textContent = item.label;
        opt.onclick = () => {
          valueInput.value = item.id;
          labelEl.innerHTML = escapeHtml(item.label);
          list.querySelectorAll(".aselect-option").forEach((o) => o.classList.toggle("selected", o === opt));
          dd.classList.remove("show");
        };
        list.appendChild(opt);
      });
      if (!items.length && page === 1) {
        list.innerHTML = `<div class="text-muted" style="padding:10px;font-size:12.5px">لا توجد نتائج</div>`;
      }
      hasMore = !!more;
      page += 1;
    } catch (err) {
      list.innerHTML = `<div class="error-box show" style="margin:8px">${escapeHtml(err.message || "فشل التحميل")}</div>`;
    } finally {
      loadingNow = false;
      loading.classList.remove("show");
    }
  }

  ctrl.onclick = () => {
    const isOpen = dd.classList.toggle("show");
    if (isOpen && !loadedOnce) {
      loadedOnce = true;
      loadMore(true);
    }
  };

  let debounce;
  search.addEventListener("input", () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => { term = search.value.trim(); loadMore(true); }, 350);
  });

  list.addEventListener("scroll", () => {
    if (list.scrollTop + list.clientHeight >= list.scrollHeight - 40) loadMore(false);
  });

  if (resolveLabel && valueInput.value && !currentLabelAlreadySet(labelEl)) {
    resolveLabel(valueInput.value).then((label) => {
      if (label) labelEl.textContent = label;
    }).catch(() => {});
  }
}

function currentLabelAlreadySet(labelEl) {
  return !labelEl.querySelector(".text-muted");
}

function readAsyncSelect(id) {
  const v = document.getElementById(id + "_value")?.value;
  return v ? Number(v) : undefined;
}

function confirmDialog(message, onConfirm) {
  Modal.open({
    title: "تأكيد",
    bodyHtml: `<p>${escapeHtml(message)}</p>`,
    footerHtml: `
      <button class="btn btn-outline" id="cdCancel">إلغاء</button>
      <button class="btn btn-danger" id="cdOk">تأكيد الحذف</button>`,
  });
  document.getElementById("cdCancel").onclick = () => Modal.close();
  document.getElementById("cdOk").onclick = async () => {
    const btn = document.getElementById("cdOk");
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner"></span>`;
    try {
      await onConfirm();
      Modal.close();
    } catch (err) {
      toast(err.message || "فشلت العملية", "error");
      btn.disabled = false;
      btn.textContent = "تأكيد الحذف";
    }
  };
}

/**
 * Renders a media-field widget (image or video) with two options: URL link OR local upload.
 * The API accepts real uploaded files (multipart FormData) or a plain URL string for the same
 * field name — the backend saves the uploaded file and stores its URL. When a file is chosen
 * here, the whole form is submitted as FormData with the actual File object (see readMediaField
 * + buildRequestBody), not a base64 string.
 */
function imageFieldHtml(id, currentUrl = "", opts = {}) {
  const kind = opts.kind || "image"; // "image" | "video" | "file"
  const label = opts.label || (kind === "video" ? "الفيديو التعريفي (اختياري)" : kind === "file" ? "الملف (اختياري)" : "الصورة الشخصية (اختياري)");
  const accept = kind === "video" ? "video/*" : kind === "file" ? "*/*" : "image/*";
  const modeLabel = kind === "video" ? "فيديو" : kind === "file" ? "ملف" : "صورة";
  let previewHtml;
  if (kind === "video") {
    previewHtml = `<video id="${id}_preview" class="img-preview ${currentUrl ? "show" : ""}" src="${escapeHtml(currentUrl)}" controls style="width:220px;height:auto;border-radius:10px"></video>`;
  } else if (kind === "file") {
    previewHtml = `<a id="${id}_preview" class="file-preview ${currentUrl ? "show" : ""}" href="${escapeHtml(currentUrl)}" target="_blank" rel="noopener">📄 <span id="${id}_preview_name">${escapeHtml(currentUrl)}</span></a>`;
  } else {
    previewHtml = `<img id="${id}_preview" class="img-preview ${currentUrl ? "show" : ""}" src="${escapeHtml(currentUrl)}">`;
  }
  return `
    <div class="field" data-media-field="${id}" data-kind="${kind}">
      <label>${label}</label>
      <div class="img-toggle">
        <button type="button" class="active" data-mode="url" data-target="${id}">🔗 رابط ${modeLabel}</button>
        <button type="button" data-mode="upload" data-target="${id}">📤 رفع من الجهاز</button>
      </div>
      <input type="text" id="${id}_url" placeholder="https://example.com/${kind === "video" ? "video.mp4" : kind === "file" ? "file.pdf" : "avatar.png"}" value="${escapeHtml(currentUrl)}">
      <input type="file" id="${id}_file" accept="${accept}" style="display:none">
      ${previewHtml}
    </div>`;
}

function wireImageField(id) {
  const wrap = document.querySelector(`[data-media-field="${id}"]`);
  if (!wrap) return;
  const kind = wrap.dataset.kind;
  const urlInput = document.getElementById(id + "_url");
  const fileInput = document.getElementById(id + "_file");
  const preview = document.getElementById(id + "_preview");
  const nameEl = document.getElementById(id + "_preview_name");

  function setPreview(url) {
    if (kind === "file") {
      preview.href = url;
      if (nameEl) nameEl.textContent = url;
      preview.classList.toggle("show", !!url);
    } else {
      preview.src = url;
      preview.classList.toggle("show", !!url);
    }
  }

  wrap.querySelectorAll(".img-toggle button").forEach((btn) => {
    btn.onclick = () => {
      wrap.querySelectorAll(".img-toggle button").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      if (btn.dataset.mode === "url") {
        urlInput.style.display = "";
        fileInput.style.display = "none";
        fileInput.value = "";
        delete fileInput.dataset.hasFile;
        setPreview(urlInput.value);
      } else {
        urlInput.style.display = "none";
        fileInput.style.display = "";
      }
    };
  });
  urlInput.addEventListener("input", () => setPreview(urlInput.value));
  fileInput.addEventListener("change", () => {
    const f = fileInput.files[0];
    if (!f) return;
    setPreview(kind === "file" ? f.name : URL.createObjectURL(f));
    fileInput.dataset.hasFile = "1";
  });
}

/**
 * Reads back the resolved media value: a File object if an upload was chosen, the URL text
 * otherwise, or "" if empty. Pass the File straight into a FormData payload (see buildRequestBody).
 */
function readImageField(id) {
  const urlInput = document.getElementById(id + "_url");
  const fileInput = document.getElementById(id + "_file");
  if (fileInput && fileInput.style.display !== "none" && fileInput.files[0]) {
    return fileInput.files[0];
  }
  return urlInput ? urlInput.value.trim() : "";
}

/**
 * Turns a flat payload object into a request body. If any value is a File (an upload was chosen
 * in a media field), returns a real multipart FormData with the actual binary file attached -
 * for every method (POST and PUT alike). Otherwise returns the payload as-is for plain JSON.
 */
async function buildRequestBody(payload) {
  const hasFile = Object.values(payload).some((v) => v instanceof File);
  if (!hasFile) return payload;

  const fd = new FormData();
  Object.entries(payload).forEach(([key, val]) => {
    if (val === undefined || val === null) return;
    if (val instanceof File) {
      fd.append(key, val);
    } else if (Array.isArray(val)) {
      val.forEach((item) => fd.append(`${key}[]`, item));
    } else {
      fd.append(key, val);
    }
  });
  return fd;
}

function multiselectHtml(id, options, selectedIds = []) {
  const sel = new Set((selectedIds || []).map(String));
  return `
    <div class="multiselect-box" id="${id}">
      ${options
        .map(
          (o) => `<label>
            <input type="checkbox" value="${o.id}" ${sel.has(String(o.id)) ? "checked" : ""}>
            ${escapeHtml(o.label)}
          </label>`
        )
        .join("") || `<span class="text-muted">لا توجد عناصر</span>`}
    </div>`;
}
function readMultiselect(id) {
  return Array.from(document.querySelectorAll(`#${id} input[type=checkbox]:checked`)).map((c) => Number(c.value));
}

/** Builds the list of page numbers to show, with `null` standing in for an ellipsis. */
function paginationRange(page, totalPages) {
  const pages = [];
  if (totalPages <= 7) {
    for (let i = 1; i <= totalPages; i++) pages.push(i);
    return pages;
  }
  pages.push(1);
  if (page > 3) pages.push(null);
  const start = Math.max(2, page - 1);
  const end = Math.min(totalPages - 1, page + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (page < totalPages - 2) pages.push(null);
  pages.push(totalPages);
  return pages;
}

const PER_PAGE_OPTIONS = [10, 20, 50, 100];

function paginationHtml(page, totalPages, total, perPage) {
  totalPages = totalPages || 1;
  perPage = perPage || 10;
  const numbers = paginationRange(page, totalPages)
    .map((n) =>
      n === null
        ? `<button type="button" class="pg-ellipsis" disabled>…</button>`
        : `<button type="button" data-pg="${n}" class="${n === page ? "active" : ""}">${n}</button>`
    )
    .join("");
  return `
    <div class="pagination">
      <div class="pg-info">
        <span>إجمالي العناصر: ${total ?? "-"}</span>
        <label class="perpage-label">
          لكل صفحة
          <select data-perpage>
            ${PER_PAGE_OPTIONS.map((n) => `<option value="${n}" ${n === perPage ? "selected" : ""}>${n}</option>`).join("")}
          </select>
        </label>
      </div>
      <div class="pgbtns">
        <button type="button" data-pg="prev" ${page <= 1 ? "disabled" : ""}>‹ السابق</button>
        ${numbers}
        <button type="button" data-pg="next" ${page >= totalPages ? "disabled" : ""}>التالي ›</button>
      </div>
    </div>`;
}

/** Renders a row of small stat chips from a generic `statistics` object returned alongside a list. */
const STAT_LABELS = {
  total_count: "الإجمالي",
  total: "الإجمالي",
  active_count: "مفعّل",
  active: "مفعّل",
  inactive_count: "غير مفعّل",
  inactive: "غير مفعّل",
  pending_count: "بانتظار التفعيل",
  pending: "بانتظار التفعيل",
  verified_count: "مؤكد بريديًا",
  email_verified_count: "مؤكد بريديًا",
};
const STAT_COLORS = {
  total_count: "primary", total: "primary",
  active_count: "success", active: "success",
  inactive_count: "danger", inactive: "danger",
  pending_count: "warning", pending: "warning",
  verified_count: "success", email_verified_count: "success",
};
const STAT_ICONS = {
  total_count: "📋", total: "📋",
  active_count: "✅", active: "✅",
  inactive_count: "⛔", inactive: "⛔",
  pending_count: "⏳", pending: "⏳",
  verified_count: "📧", email_verified_count: "📧",
};
/**
 * Wires a pagination container: numeric buttons jump to that page, prev/next step by one,
 * and the per-page <select> updates state.perPage (resetting to page 1). Uses a single
 * delegated click/change listener on the container so it keeps working after re-renders.
 */
function wirePagination(containerEl, state, onChange) {
  containerEl.onclick = (e) => {
    const btn = e.target.closest("[data-pg]");
    if (!btn || btn.disabled) return;
    const v = btn.dataset.pg;
    if (v === "next") state.page += 1;
    else if (v === "prev") state.page -= 1;
    else state.page = Number(v);
    onChange();
  };
  const perPageSelect = containerEl.querySelector("[data-perpage]");
  if (perPageSelect) {
    perPageSelect.onchange = () => {
      state.perPage = Number(perPageSelect.value);
      state.page = 1;
      onChange();
    };
  }
}

function statsBarHtml(stats) {
  if (!stats || typeof stats !== "object" || !Object.keys(stats).length) return "";
  return `
    <div class="stats-bar">
      ${Object.entries(stats)
        .map(([k, v]) => {
          const color = STAT_COLORS[k] || "muted";
          const label = STAT_LABELS[k] || k;
          const icon = STAT_ICONS[k] || "📊";
          return `<div class="stat-chip stat-chip-${color}">
            <span class="stat-chip-icon">${icon}</span>
            <span class="stat-chip-text">
              <span class="stat-chip-value">${v}</span>
              <span class="stat-chip-label">${escapeHtml(label)}</span>
            </span>
          </div>`;
        })
        .join("")}
    </div>`;
}

function statusBadge(active) {
  return active ? `<span class="badge badge-success">مفعّل</span>` : `<span class="badge badge-danger">غير مفعّل</span>`;
}
function verifiedBadge(v) {
  return v ? `<span class="badge badge-success">مؤكد</span>` : `<span class="badge badge-warning">غير مؤكد</span>`;
}
