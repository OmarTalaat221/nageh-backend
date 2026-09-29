<?php

/**
 * Get active languages list from database (cached statically per request)
 */
function get_active_languages($con): array {
    static $activeLangs = null;

    if ($activeLangs !== null) {
        return $activeLangs;
    }

    $activeLangs = [];
    if (!$con) {
        return $activeLangs;
    }

    $sql = "SELECT id, code, direction, is_default FROM languages WHERE is_active = 1 ORDER BY is_default DESC, sort_order ASC, id ASC";
    $res = mysqli_query($con, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $row['id'] = (int)$row['id'];
            $row['is_default'] = (int)$row['is_default'];
            $activeLangs[$row['code']] = $row;
        }
    }

    return $activeLangs;
}

/**
 * Detect requested language dynamically from request headers or query params.
 * Falls back to active default language or first active language in DB if not found/inactive.
 */
/**
 * Check if a language header or query param was explicitly provided by the client
 */
function has_request_lang_header(): bool {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $rawHeader = $headers['Accept-Language'] 
        ?? $headers['accept-language'] 
        ?? $headers['lang'] 
        ?? $headers['Lang'] 
        ?? $headers['X-Lang'] 
        ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] 
        ?? $_GET['lang'] 
        ?? '';

    return !empty(trim((string)$rawHeader));
}

/**
 * Check if current HTTP request target path is an admin endpoint
 */
function is_admin_request(): bool {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    return str_contains($uri, '/admin/');
}

if (!function_exists('is_teacher_request')) {
    function is_teacher_request(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/teacher/');
    }
}

if (!function_exists('is_admin_or_teacher_request')) {
    function is_admin_or_teacher_request(): bool {
        if (function_exists('is_admin_request') && is_admin_request()) {
            return true;
        }
        if (is_teacher_request()) {
            return true;
        }
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = $matches[1];
            if (function_exists('verifyTeacherAccessToken') && verifyTeacherAccessToken($token)) {
                return true;
            }
            if (function_exists('verifyAdminAccessToken') && verifyAdminAccessToken($token)) {
                return true;
            }
        }
        return false;
    }
}

function normalize_lang_code(string $code): string {
    $clean = strtolower(trim($code));
    if (empty($clean)) return '';
    if ($clean === '1' || str_starts_with($clean, 'ar') || $clean === 'arabic') return 'ar';
    if ($clean === '2' || str_starts_with($clean, 'en') || $clean === 'english') return 'en';
    if ($clean === '3' || str_starts_with($clean, 'fr') || $clean === 'french') return 'fr';
    if (preg_match('/^([a-z]{2})/i', $clean, $m)) {
        return strtolower($m[1]);
    }
    return $clean;
}

function get_request_lang($con = null): string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    
    $raw = $headers['Accept-Language'] 
        ?? $headers['accept-language'] 
        ?? $headers['lang'] 
        ?? $headers['Lang'] 
        ?? $headers['X-Lang'] 
        ?? $headers['language']
        ?? $headers['Language']
        ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] 
        ?? $_SERVER['HTTP_LANG']
        ?? $_GET['lang'] 
        ?? $_GET['language']
        ?? $_GET['language_id']
        ?? $_GET['lang_id']
        ?? $_POST['lang']
        ?? $_POST['language']
        ?? $_POST['language_id']
        ?? '';

    if (empty($raw) && function_exists('read_json_body_raw_safe')) {
        $body = read_json_body_raw_safe();
        $raw = $body['lang'] ?? $body['language'] ?? $body['language_id'] ?? '';
    }

    $requestedCode = normalize_lang_code((string)$raw);

    if ($con) {
        $activeLangs = get_active_languages($con);
        
        if (!empty($requestedCode) && isset($activeLangs[$requestedCode])) {
            return $requestedCode;
        }

        foreach ($activeLangs as $lang) {
            if ($lang['is_default'] === 1) {
                return $lang['code'];
            }
        }

        if (!empty($activeLangs)) {
            $first = reset($activeLangs);
            return $first['code'];
        }
    }

    return !empty($requestedCode) ? $requestedCode : 'ar';
}

/**
 * Get language info array (id, code, direction) from database
 */
function get_language_info($con): array {
    static $langCache = [];
    $langCode = get_request_lang($con);

    if (isset($langCache[$langCode])) {
        return $langCache[$langCode];
    }

    $activeLangs = get_active_languages($con);
    if (isset($activeLangs[$langCode])) {
        $langCache[$langCode] = $activeLangs[$langCode];
        return $langCache[$langCode];
    }

    $fallback = ($langCode === 'ar') 
        ? ['id' => 1, 'code' => 'ar', 'direction' => 'rtl', 'is_default' => 0] 
        : ['id' => 2, 'code' => 'en', 'direction' => 'ltr', 'is_default' => 1];

    $langCache[$langCode] = $fallback;
    return $fallback;
}

/**
 * Dynamic Translation Dictionary for API Responses
 */
function trans(string $key, ?string $lang = null, $con = null): string {
    $lang = $lang ?: get_request_lang($con);

    $messages = [
        'ar' => [
            'invalid_json' => 'بيانات JSON غير صالحة',
            // Neutral since migration 079: a student may log in with a phone number, and being
            // told "email is required" after typing a phone number reads as a rejection of the
            // phone number itself.
            'email_password_required' => 'البريد الإلكتروني أو رقم الهاتف وكلمة المرور مطلوبان',
            'phone_on_multiple_accounts' => 'رقم الهاتف ده مسجّل على أكثر من حساب. سجّل الدخول بالبريد الإلكتروني.',
            'phone_already_registered' => 'رقم الهاتف ده مسجّل على حساب آخر بالفعل.',
            'name_email_password_required' => 'الاسم والبريد الإلكتروني وكلمة المرور مطلوبة',
            'invalid_email' => 'صيغة البريد الإلكتروني غير صالحة',
            'short_password' => 'كلمة المرور يجب أن لا تقل عن 6 أحرف',
            'invalid_credentials' => 'بيانات الدخول أو كلمة المرور غير صحيحة',
            'account_inactive' => 'الحساب غير مفعل، يرجى التواصل مع الدعم الفني',
            'teacher_suspension_reason' => 'تم إيقاف حسابك تلقائيًا لتجاوزك الحد المسموح به من الغياب بدون إذن. تواصل مع الإدارة عبر المحادثة لمزيد من التفاصيل.',
            'super_admin_only' => 'هذا الإجراء متاح للمدير العام فقط',
            'subscription_updated' => 'تم تعديل الاشتراك',
            'subscription_start_required' => 'تاريخ البداية مطلوب',
            'subscription_invalid_date' => 'صيغة التاريخ غير صحيحة',
            'subscription_end_before_start' => 'تاريخ النهاية لا يمكن أن يسبق تاريخ البداية',
            'subscription_unknown_item' => 'أحد العناصر المختارة غير موجود',
            'search_too_short' => 'اكتب حرفين على الأقل للبحث',
            'academic_year_name_required' => 'اسم الصف بالعربية مطلوب',
            'subject_name_required' => 'اسم المادة بالعربية مطلوب',
            'subscription_not_found' => 'الاشتراك غير موجود',
            'subscription_status_updated' => 'تم تحديث حالة الاشتراك',
            'subscription_expired_cannot_resume' => 'الاشتراك منتهي بالفعل، مدّ تاريخ الانتهاء أولاً',
            'student_subscription_frozen_reason' => 'تم تجميد اشتراكك تلقائيًا لتجاوزك الحد المسموح به من الغياب بدون إذن. تواصل مع الإدارة عبر المحادثة لمزيد من التفاصيل.',
            'account_pending_approval' => 'حسابك قيد المراجعة والتفعيل وبانتظار موافقة الأدمن',
            'email_not_verified' => 'يرجى تأكيد البريد الإلكتروني أولاً لتفعيل حسابك',
            'otp_sent_success' => 'تم إرسال رمز التحقق إلى البريد الإلكتروني بنجاح',
            'email_verified_success' => 'تم تأكيد البريد الإلكتروني بنجاح',
            'invalid_otp_code' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
            'email_already_registered' => 'البريد الإلكتروني مسجل بالفعل',
            'admin_login_success' => 'تم تسجيل دخول الأدمن بنجاح',
            'admin_register_success' => 'تم إنشاء حساب الأدمن بنجاح',
            'teacher_login_success' => 'تم تسجيل دخول المعلم بنجاح',
            'teacher_register_success' => 'تم إنشاء حساب المعلم بنجاح وتأكيد البريد مطلوب قبل تفعيل الأدمن',
            'student_login_success' => 'تم تسجيل دخول الطالب بنجاح',
            'student_register_success' => 'تم إنشاء حساب الطالب بنجاح، يرجى تأكيد البريد الإلكتروني',
            'parent_login_success' => 'تم تسجيل دخول ولي الأمر بنجاح',
            'parent_register_success' => 'تم إنشاء حساب ولي الأمر بنجاح، يرجى تأكيد البريد الإلكتروني',
            'profile_not_found' => 'لم يتم العثور على الملف الشخصي',
            'profile_fetched_success' => 'تم جلب بيانات الملف الشخصي بنجاح',
            'tokens_refreshed_success' => 'تم تجديد التوكين بنجاح',
            'refresh_token_required' => 'رمز التجديد (refresh_token) مطلوب',
            'invalid_refresh_token' => 'رمز التجديد غير صالح أو منتهي الصلاحية',
            'unauthorized' => 'غير مصرح بالدخول: التوكين غير صالح أو منتهي الصلاحية',
            'permission_denied' => 'عذراً، ليس لديك الصلاحية الكافية لتنفيذ هذا الإجراء',
            'student_already_linked' => 'الطالب مرتبط بالفعل بولي الأمر',
            'student_linked_success' => 'تم ربط الطالب بولي الأمر بنجاح',
            'student_unlinked_success' => 'تم فك ربط الطالب من ولي الأمر بنجاح',
            'student_not_found' => 'لم يتم العثور على الطالب المطلوب',
            'parent_not_found' => 'لم يتم العثور على ولي الأمر المطلوب',
            'teacher_not_found' => 'لم يتم العثور على المعلم المطلوب',
            'teacher_id_required' => 'اختيار المعلم مطلوب',
            'admin_not_found' => 'لم يتم العثور على المسؤول المطلوب',
            'role_not_found' => 'لم يتم العثور على الرول المطلوب',
            'subject_id_required' => 'يجب اختيار مادة واحدة على الأقل (subject_id أو subject_ids)',
            'student_created_success' => 'تم إنشاء حساب الطالب بنجاح',
            'student_updated_success' => 'تم تحديث بيانات الطالب بنجاح',
            'student_deleted_success' => 'تم حذف الطالب بنجاح',
            'teacher_created_success' => 'تم إنشاء حساب المعلم بنجاح',
            'teacher_updated_success' => 'تم تحديث بيانات المعلم بنجاح',
            'teacher_deleted_success' => 'تم حذف المعلم بنجاح',
            'parent_created_success' => 'تم إنشاء حساب ولي الأمر بنجاح',
            'parent_updated_success' => 'تم تحديث بيانات ولي الأمر بنجاح',
            'parent_deleted_success' => 'تم حذف ولي الأمر بنجاح',
            'role_created_success' => 'تم إنشاء الرول بنجاح',
            'role_updated_success' => 'تم تحديث الرول بنجاح',
            'role_deleted_success' => 'تم حذف الرول بنجاح',
            'admin_user_created_success' => 'تم إنشاء حساب المسؤول بنجاح',
            'admin_user_updated_success' => 'تم تحديث حساب المسؤول بنجاح',
            'admin_user_deleted_success' => 'تم حذف حساب المسؤول بنجاح',
            'cannot_delete_self' => 'لا يمكنك حذف حسابك الشخصي الحالي',
            'password_reset_code_sent' => 'تم إرسال رمز إعادة تعيين كلمة المرور إلى بريدك الإلكتروني بنجاح',
            'code_verified_success' => 'تم التحقق من الرمز بنجاح',
            'password_reset_success' => 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول',
            'route_not_found' => 'المسار غير موجود',
            'session_id_required' => 'رقم الحصة (session_id) مطلوب',
            'session_not_found' => 'الحصة غير موجودة',
            'session_not_found_or_not_owned' => 'الحصة غير موجودة أو لا تملك صلاحية الوصول إليها',
            'session_file_required' => 'رقم ملف الحصة (session_file_id) مطلوب',
            'session_file_not_found' => 'ملف الحصة غير موجود',
            'session_file_not_found_or_not_owned' => 'ملف الحصة غير موجود أو لا تملك صلاحية الوصول إليه',
            'file_id_required' => 'رقم الملف مطلوب',
            'file_not_found' => 'الملف غير موجود',
            'file_not_found_or_not_owned' => 'الملف غير موجود أو لا تملك صلاحية الوصول إليه',
            'submission_id_required' => 'رقم التسليم مطلوب',
            'submission_not_found' => 'التسليم غير موجود',
            'submission_not_found_or_not_yours' => 'التسليم غير موجود أو ليس ضمن حصصك',
            'grade_or_feedback_required' => 'يجب إدخال الدرجة أو الملاحظات',
            'not_subscribed_to_session' => 'أنت غير مشترك في هذه الحصة',
            'attendance_table_missing' => 'جدول الحضور غير مُنشأ بعد، يرجى تشغيل ملف الترحيل الخاص به',
            'session_id_and_date_required' => 'رقم الحصة وتاريخها مطلوبان',
            'request_type_required' => 'نوع الطلب مطلوب (غياب بإذن أو إبلاغ تأخير)',
            'reason_required' => 'سبب الطلب مطلوب',
            'expected_minutes_late_required' => 'عدد دقائق التأخير المتوقعة مطلوب',
            'request_already_pending' => 'يوجد بالفعل طلب قيد المراجعة لنفس الحصة والتاريخ',
            'request_sent_success' => 'تم إرسال الطلب بنجاح للإدارة',
            'request_reviewed_success' => 'تمت مراجعة الطلب بنجاح',
            'request_not_found_or_reviewed' => 'الطلب غير موجود أو تمت مراجعته بالفعل',
            'id_and_decision_required' => 'رقم الطلب وقرار صالح (approved/rejected) مطلوبان',
            'wallet_transaction_id_required' => 'رقم حركة المحفظة مطلوب',
            'wallet_transaction_not_found' => 'حركة المحفظة غير موجودة',
            'only_fines_can_be_appealed' => 'يمكن الاعتراض على حركات الغرامات فقط',
            'appeal_already_exists' => 'تم تقديم اعتراض على هذا الخصم بالفعل',
            'appeal_submitted_success' => 'تم إرسال الاعتراض بنجاح للإدارة',
            'appeal_reviewed_success' => 'تمت مراجعة الاعتراض بنجاح',
            'appeal_not_found_or_reviewed' => 'الاعتراض غير موجود أو تمت مراجعته بالفعل',
            'amount_required' => 'المبلغ مطلوب ويجب ألا يساوي صفر',
            'note_required' => 'ملاحظة توضيحية للتعديل اليدوي مطلوبة',
            'wallet_adjusted_success' => 'تم تعديل رصيد المحفظة بنجاح',
            'attendance_suspension_lifted_success' => 'تم إلغاء إيقاف المعلم بسبب الغياب بنجاح',
            'pay_model_required' => 'طريقة احتساب الأجر مطلوبة (ساعة/حصة/دقيقة)',
            'rate_amount_required' => 'قيمة السعر مطلوبة',
            'pay_rate_saved_success' => 'تم حفظ سعر المعلم الخاص بنجاح',
            'pay_rate_reset_success' => 'تم إعادة تعيين سعر المعلم للسعر الافتراضي',
            'settings_updated_success' => 'تم تحديث الإعدادات بنجاح',
            'max_reschedule_requests_reached' => 'تم الوصول للحد الأقصى من طلبات التأجيل المسموح بها هذا الشهر',
            'check_in_recorded_success' => 'تم تسجيل الدخول للحصة بنجاح',
            'session_start_too_early' => 'لا يمكنك بدء الحصة الآن، يمكنك بدء الحصة قبل موعدها بخمس دقائق فقط',
            'file_upload_required' => 'يجب إرفاق ملف (رفع مباشر أو رابط file_path)',
            'solution_file_required' => 'يجب إرفاق ملف الحل',
            'notifications_fetched_success' => 'تم جلب الإشعارات بنجاح',
            'notification_marked_read' => 'تم تحديد الإشعار كمقروء',
            'all_notifications_marked_read' => 'تم تحديد جميع الإشعارات كمقروءة',
            'notification_not_found' => 'لم يتم العثور على الإشعار',
            'notification_deleted_success' => 'تم حذف الإشعار بنجاح',
            'audio_upload_success' => 'تم رفع الملف الصوتي بنجاح',
            'audio_file_required' => 'يرجى إرفاق ملف صوتي صالحة',
            'profile_updated_success' => 'تم تحديث بيانات الملف الشخصي بنجاح',
            'no_fields_to_update' => 'لم يتم إرسال بيانات جديدة لتعديلها',
            'email_in_use' => 'البريد الإلكتروني مستخدم بالفعل بحساب آخر',
            'invalid_study_language' => 'لغة الدراسة غير صالحة',
            'payment_initiated' => 'تم بدء عملية الدفع بنجاح',
            'payment_confirmed_access_granted' => 'تم تأكيد الدفع وتفعيل الاشتراك بنجاح',
            'item_not_found_or_unavailable' => 'العنصر غير موجود أو غير متاح',
            'already_have_access' => 'لديك صلاحية الوصول لهذا العنصر بالفعل',
            'item_is_free' => 'هذا العنصر مجاني ولا يتطلب دفعاً',
            'invalid_subscription_code' => 'كود التفعيل غير صحيح',
            'subscription_code_expired' => 'كود التفعيل منتهي الصلاحية',
            'subscription_code_used' => 'تم استخدام كود التفعيل من قبل',
            'subscription_code_redeemed_success' => 'تم استخدام كود التفعيل وتفعيل الاشتراك بنجاح',
            'subscription_code_required' => 'كود التفعيل مطلوب',
            'student_auth_required' => 'يلزم تسجيل دخول الطالب',
            'affiliate_reward_approved' => 'تم قبول مكافأة الإحالة بنجاح',
            'affiliate_request_rejected' => 'تم رفض طلب الإحالة بنجاح',
            'points_deducted_success' => 'تم خصم النقاط بنجاح من حساب الطالب',
            'insufficient_points' => 'رصيد نقاط الطالب غير كافٍ',
            'amount_cannot_be_negative' => 'المبلغ أو النقاط يجب أن يكون رقماً موجباً',
            'commission_id_required' => 'رقم طلب العمولة مطلوب',
            'student_id_required' => 'رقم الطالب مطلوب',
            'video_progress_updated' => 'تم تحديث نسبة مشاهدة الفيديو بنجاح',
            'book_opening_tracked' => 'تم تسجيل فتح الكتاب بنجاح',
            'file_uploaded_success' => 'تم رفع الملف بنجاح',
            'slot_id_required' => 'رقم موعد الاستشارة مطلوب',
            'invalid_meeting_type' => 'نوع طريقة المقابلة غير صالح',
            'slot_date_time_required' => 'تاريخ ووقت الاستشارة مطلوبان',
            'slot_added_success' => 'تم إضافة موعد الاستشارة بنجاح',
            'at_least_one_day_required' => 'اختر يومًا واحدًا على الأقل',
            'slot_time_required' => 'وقت الاستشارة مطلوب',
            'end_date_required' => 'تاريخ النهاية مطلوب',
            'end_date_must_be_in_future' => 'تاريخ النهاية يجب أن يكون اليوم أو بعده',
            'end_date_too_far' => 'تاريخ النهاية بعيد جدًا (الحد الأقصى 180 يومًا)',
            'no_dates_in_range' => 'لا توجد مواعيد ضمن هذا النطاق',
            'too_many_slots_requested' => 'عدد المواعيد المطلوبة كبير جدًا، قلل المدة أو عدد الأيام',
            'recurring_slots_created_success' => 'تم إنشاء مواعيد الاستشارة المتكررة بنجاح',
            'consultation_not_found_or_not_booked' => 'طلب الاستشارة غير موجود أو غير محجوز بعد',
            'consultation_approved_success' => 'تم الموافقة على حجز الاستشارة بنجاح',
            'consultation_cancelled_restored' => 'تم إلغاء حجز الاستشارة وإعادة الموعد للمواعيد المتاحة',
            'invalid_action_approve_reject' => 'الإجراء يجب أن يكون قبول أو رفض أو إلغاء',
            'consultation_reset_available' => 'تم إعادة الموعد متاحاً للحجز',
            'consultation_deleted_success' => 'تم حذف موعد الاستشارة بنجاح',
            'slot_no_longer_available' => 'عذراً، هذا الموعد لم يعد متاحاً للحجز',
            'slot_booked_awaiting_approval' => 'تم طلب حجز الاستشارة بنجاح وفي انتظار موافقة المعلم',
            // An admin booking is not awaiting anyone, so it must not say that it is.
            'slot_booked_for_student' => 'تم حجز الموعد للطالب وإرسال إشعار له وللمعلم',
            'booking_not_found_or_cancelled' => 'الحجز غير موجود أو تم إلغاؤه بالفعل',
            'consultation_cancelled_success' => 'تم إلغاء حجز الاستشارة بنجاح',
            'consultation_slot_not_found' => 'موعد الاستشارة غير موجود',
            'consultation_updated_success' => 'تم تحديث موعد الاستشارة بنجاح',
            'Subscription activation code is required' => 'كود تفعيل الاشتراك مطلوب',
            'Student authentication is required to redeem code' => 'يلزم تسجيل دخول الطالب لاستخدام الكود',
            'Invalid subscription code' => 'كود التفعيل غير صحيح',
            'Subscription code is inactive' => 'كود التفعيل غير مفعل',
            'Subscription code is not active yet' => 'كود التفعيل ليس نشطاً بعد',
            'Subscription code has expired' => 'كود التفعيل منتهي الصلاحية',
            'Subscription code has already been used and cannot be used again (Single-use code)' => 'تم استخدام كود التفعيل من قبل ولا يمكن استخدامه مجدداً',
            'You have already redeemed this subscription code' => 'لقد قمت باستخدام كود التفعيل هذا مسبقاً',
            'This subscription code has no linked content or targets' => 'كود التفعيل هذا غير مرتبط بأي محتوى',
            'Subscription code redeemed and activated successfully' => 'تم استخدام كود التفعيل وتفعيل الاشتراك بنجاح',
            'Student authentication is required' => 'يلزم تسجيل دخول الطالب',
            'video_id is required' => 'رقم الفيديو (video_id) مطلوب',
            'Video not found' => 'الفيديو غير موجود',
            'You are not subscribed to the course containing this video' => 'أنت غير مشترك في الكورس التابع له هذا الفيديو',
            'Video progress updated successfully' => 'تم تحديث نسبة مشاهدة الفيديو بنجاح',
            'book_id is required' => 'رقم الكتاب (book_id) مطلوب',
            'Book not found' => 'الكتاب غير موجود',
            'You are not subscribed to the content containing this book' => 'أنت غير مشترك في المحتوى التابع له هذا الكتاب',
            'Book opening tracked successfully' => 'تم تسجيل فتح الكتاب بنجاح',
            'Invalid signature' => 'توقيع العملية غير صالح',
            'Missing customerReference' => 'مرجع المعاملة مفقود',
            'Unknown transaction' => 'معاملة غير معروفة',
            'This transaction does not belong to you' => 'هذه المعاملة لا تتبع حسابك',
            'Transaction already processed' => 'تم معالجة هذه المعاملة مسبقاً',
            'Callback recorded' => 'تم تسجيل العملية بنجاح',
            'Transaction not found' => 'المعاملة غير موجودة',
            'Invalid request' => 'طلب غير صالح',
        ],
        'en' => [
            'invalid_json' => 'Invalid JSON data',
            'email_password_required' => 'Email or phone number and password are required',
            'phone_on_multiple_accounts' => 'This phone number is on more than one account. Please sign in with your email.',
            'phone_already_registered' => 'This phone number is already registered to another account.',
            'name_email_password_required' => 'Name, email, and password are required',
            'invalid_email' => 'Invalid email format',
            'short_password' => 'Password must be at least 6 characters long',
            'invalid_credentials' => 'Invalid credentials or password',
            'account_inactive' => 'Account is inactive. Please contact support.',
            'teacher_suspension_reason' => 'Your account was automatically suspended for exceeding the allowed number of unauthorized absences. Contact the administration via chat for more details.',
            'super_admin_only' => 'This action is available to the super admin only',
            'subscription_updated' => 'The subscription has been updated',
            'subscription_start_required' => 'A start date is required',
            'subscription_invalid_date' => 'That date is not in a valid format',
            'subscription_end_before_start' => 'The end date cannot be before the start date',
            'subscription_unknown_item' => 'One of the selected items does not exist',
            'search_too_short' => 'Type at least two characters to search',
            'academic_year_name_required' => 'The Arabic name of the grade is required',
            'subject_name_required' => 'The Arabic name of the subject is required',
            'subscription_not_found' => 'Subscription not found',
            'subscription_status_updated' => 'The subscription status has been updated',
            'subscription_expired_cannot_resume' => 'This subscription has already expired. Extend its end date first.',
            'student_subscription_frozen_reason' => 'Your subscription was automatically frozen for exceeding the allowed number of unauthorized absences. Contact the administration via chat for more details.',
            'account_pending_approval' => 'Your account is pending admin approval',
            'email_not_verified' => 'Please verify your email address first',
            'otp_sent_success' => 'Verification code sent to your email successfully',
            'email_verified_success' => 'Email address verified successfully',
            'invalid_otp_code' => 'Invalid or expired verification code',
            'email_already_registered' => 'Email is already registered',
            'admin_login_success' => 'Admin logged in successfully',
            'admin_register_success' => 'Admin account created successfully',
            'teacher_login_success' => 'Teacher logged in successfully',
            'teacher_register_success' => 'Teacher registered successfully. Email verification required.',
            'student_login_success' => 'Student logged in successfully',
            'student_register_success' => 'Student registered successfully. Please verify your email.',
            'parent_login_success' => 'Parent logged in successfully',
            'parent_register_success' => 'Parent registered successfully. Please verify your email.',
            'profile_not_found' => 'Profile not found',
            'profile_fetched_success' => 'Profile fetched successfully',
            'tokens_refreshed_success' => 'Tokens refreshed successfully',
            'refresh_token_required' => 'refresh_token is required',
            'invalid_refresh_token' => 'Invalid or expired refresh token',
            'unauthorized' => 'Unauthorized: Token is invalid or expired',
            'permission_denied' => 'Permission denied: You do not have access to perform this action',
            'student_already_linked' => 'Student is already linked to this parent',
            'student_linked_success' => 'Student linked to parent successfully',
            'student_unlinked_success' => 'Student unlinked from parent successfully',
            'student_not_found' => 'Student not found',
            'parent_not_found' => 'Parent not found',
            'teacher_not_found' => 'Teacher not found',
            'teacher_id_required' => 'Selecting a teacher is required',
            'admin_not_found' => 'Admin user not found',
            'role_not_found' => 'Role not found',
            'subject_id_required' => 'At least one subject is required (subject_id or subject_ids)',
            'student_created_success' => 'Student created successfully',
            'student_updated_success' => 'Student updated successfully',
            'student_deleted_success' => 'Student deleted successfully',
            'teacher_created_success' => 'Teacher created successfully',
            'teacher_updated_success' => 'Teacher updated successfully',
            'teacher_deleted_success' => 'Teacher deleted successfully',
            'parent_created_success' => 'Parent created successfully',
            'parent_updated_success' => 'Parent updated successfully',
            'parent_deleted_success' => 'Parent deleted successfully',
            'role_created_success' => 'Role created successfully',
            'role_updated_success' => 'Role updated successfully',
            'role_deleted_success' => 'Role deleted successfully',
            'admin_user_created_success' => 'Admin user created successfully',
            'admin_user_updated_success' => 'Admin user updated successfully',
            'admin_user_deleted_success' => 'Admin user deleted successfully',
            'cannot_delete_self' => 'You cannot delete your own admin account',
            'password_reset_code_sent' => 'Password reset code sent to your email successfully',
            'code_verified_success' => 'Code verified successfully',
            'password_reset_success' => 'Password reset successfully. You can now log in with your new password.',
            'route_not_found' => 'Route not found',
            'session_id_required' => 'session_id is required',
            'session_not_found' => 'Session not found',
            'session_not_found_or_not_owned' => 'Session not found or not owned by you',
            'session_file_required' => 'session_file_id is required',
            'session_file_not_found' => 'Session file not found',
            'session_file_not_found_or_not_owned' => 'Session file not found or not owned by you',
            'file_id_required' => 'File id is required',
            'file_not_found' => 'File not found',
            'file_not_found_or_not_owned' => 'File not found or not owned by you',
            'submission_id_required' => 'Submission id is required',
            'submission_not_found' => 'Submission not found',
            'submission_not_found_or_not_yours' => 'Submission not found or not in your sessions',
            'grade_or_feedback_required' => 'grade or feedback is required',
            'not_subscribed_to_session' => 'You are not subscribed to this session',
            'attendance_table_missing' => "The attendance table does not exist yet, please run its migration",
            'session_id_and_date_required' => 'session_id and occurrence_date are required',
            'request_type_required' => 'A valid request_type (absence_permission or late_notice) is required',
            'reason_required' => 'A reason is required',
            'expected_minutes_late_required' => 'expected_minutes_late is required',
            'request_already_pending' => 'A pending request already exists for this session and date',
            'request_sent_success' => 'Request sent to the admin successfully',
            'request_reviewed_success' => 'Request reviewed successfully',
            'request_not_found_or_reviewed' => 'Request not found or already reviewed',
            'id_and_decision_required' => 'id and a valid decision (approved/rejected) are required',
            'wallet_transaction_id_required' => 'wallet_transaction_id is required',
            'wallet_transaction_not_found' => 'Wallet transaction not found',
            'only_fines_can_be_appealed' => 'Only fine transactions can be appealed',
            'appeal_already_exists' => 'An appeal already exists for this deduction',
            'appeal_submitted_success' => 'Appeal submitted to the admin successfully',
            'appeal_reviewed_success' => 'Appeal reviewed successfully',
            'appeal_not_found_or_reviewed' => 'Appeal not found or already reviewed',
            'amount_required' => 'A non-zero amount is required',
            'note_required' => 'A note explaining the manual adjustment is required',
            'wallet_adjusted_success' => 'Wallet balance adjusted successfully',
            'attendance_suspension_lifted_success' => 'Teacher attendance suspension lifted successfully',
            'pay_model_required' => 'A valid pay_model (hourly/per_session/per_minute) is required',
            'rate_amount_required' => 'rate_amount is required',
            'pay_rate_saved_success' => 'Teacher pay rate saved successfully',
            'pay_rate_reset_success' => 'Teacher pay rate reset to the default',
            'settings_updated_success' => 'Settings updated successfully',
            'max_reschedule_requests_reached' => 'The maximum number of reschedule requests allowed this month has been reached',
            'check_in_recorded_success' => 'Check-in recorded successfully',
            'session_start_too_early' => 'You cannot start the session yet — you can only start it up to 5 minutes before its scheduled time',
            'file_upload_required' => 'A file must be provided (multipart file upload or file_path URL)',
            'solution_file_required' => 'A solution file must be provided',
            'notifications_fetched_success' => 'Notifications fetched successfully',
            'notification_marked_read' => 'Notification marked as read',
            'all_notifications_marked_read' => 'All notifications marked as read',
            'notification_not_found' => 'Notification not found',
            'notification_deleted_success' => 'Notification deleted successfully',
            'audio_upload_success' => 'Audio record uploaded successfully',
            'audio_file_required' => 'Please provide a valid audio record file',
            'profile_updated_success' => 'Profile updated successfully',
            'no_fields_to_update' => 'No fields provided to update',
            'email_in_use' => 'Email is already in use by another account',
            'invalid_study_language' => 'Invalid study language',
            'payment_initiated' => 'Payment initiated successfully',
            'payment_confirmed_access_granted' => 'Payment confirmed and access granted successfully',
            'item_not_found_or_unavailable' => 'Item not found or unavailable',
            'already_have_access' => 'You already have access to this item',
            'item_is_free' => 'This item is free and does not require payment',
            'invalid_subscription_code' => 'Invalid subscription code',
            'subscription_code_expired' => 'Subscription code has expired',
            'subscription_code_used' => 'Subscription code has already been used',
            'subscription_code_redeemed_success' => 'Subscription code redeemed and activated successfully',
            'subscription_code_required' => 'Subscription activation code is required',
            'student_auth_required' => 'Student authentication is required',
            'affiliate_reward_approved' => 'Affiliate reward approved successfully',
            'affiliate_request_rejected' => 'Affiliate request rejected successfully',
            'points_deducted_success' => 'Points deducted successfully from student account',
            'insufficient_points' => 'Insufficient points balance',
            'amount_cannot_be_negative' => 'Amount/points cannot be negative',
            'commission_id_required' => 'Valid commission_id is required',
            'student_id_required' => 'Valid student_id is required',
            'video_progress_updated' => 'Video progress updated successfully',
            'book_opening_tracked' => 'Book opening tracked successfully',
            'file_uploaded_success' => 'File uploaded successfully',
            'slot_id_required' => 'slot_id is required',
            'invalid_meeting_type' => 'Invalid meeting_type',
            'slot_date_time_required' => 'slot_date and slot_time are required',
            'slot_added_success' => 'Slot added successfully',
            'at_least_one_day_required' => 'Select at least one day',
            'slot_time_required' => 'slot_time is required',
            'end_date_required' => 'end_date is required',
            'end_date_must_be_in_future' => 'end_date must be today or later',
            'end_date_too_far' => 'end_date is too far away (max 180 days)',
            'no_dates_in_range' => 'No dates fall within this range',
            'too_many_slots_requested' => 'Too many slots requested — shorten the range or pick fewer days',
            'recurring_slots_created_success' => 'Recurring consultation slots created successfully',
            'consultation_not_found_or_not_booked' => 'Booking not found or not booked yet',
            'consultation_approved_success' => 'Consultation booking approved successfully',
            'consultation_cancelled_restored' => 'Consultation booking cancelled and slot restored to available',
            'invalid_action_approve_reject' => 'Action must be approve, reject or cancel',
            'consultation_reset_available' => 'Consultation slot reset to available',
            'consultation_deleted_success' => 'Consultation slot deleted successfully',
            'slot_no_longer_available' => 'Slot is no longer available',
            'slot_booked_awaiting_approval' => 'Slot booked successfully, awaiting teacher approval',
            'slot_booked_for_student' => 'Slot booked for the student; the student and the teacher have been notified',
            'booking_not_found_or_cancelled' => 'Booking not found or already cancelled',
            'consultation_cancelled_success' => 'Consultation booking cancelled successfully',
            'consultation_slot_not_found' => 'Consultation slot not found',
            'consultation_updated_success' => 'Consultation slot updated successfully',
            'Subscription activation code is required' => 'Subscription activation code is required',
            'Student authentication is required to redeem code' => 'Student authentication is required to redeem code',
            'Invalid subscription code' => 'Invalid subscription code',
            'Subscription code is inactive' => 'Subscription code is inactive',
            'Subscription code is not active yet' => 'Subscription code is not active yet',
            'Subscription code has expired' => 'Subscription code has expired',
            'Subscription code has already been used and cannot be used again (Single-use code)' => 'Subscription code has already been used and cannot be used again',
            'You have already redeemed this subscription code' => 'You have already redeemed this subscription code',
            'This subscription code has no linked content or targets' => 'This subscription code has no linked content or targets',
            'Subscription code redeemed and activated successfully' => 'Subscription code redeemed and activated successfully',
            'Student authentication is required' => 'Student authentication is required',
            'video_id is required' => 'video_id is required',
            'Video not found' => 'Video not found',
            'You are not subscribed to the course containing this video' => 'You are not subscribed to the course containing this video',
            'Video progress updated successfully' => 'Video progress updated successfully',
            'book_id is required' => 'book_id is required',
            'Book not found' => 'Book not found',
            'You are not subscribed to the content containing this book' => 'You are not subscribed to the content containing this book',
            'Book opening tracked successfully' => 'Book opening tracked successfully',
            'Invalid signature' => 'Invalid signature',
            'Missing customerReference' => 'Missing customerReference',
            'Unknown transaction' => 'Unknown transaction',
            'This transaction does not belong to you' => 'This transaction does not belong to you',
            'Transaction already processed' => 'Transaction already processed',
            'Callback recorded' => 'Callback recorded',
            'Transaction not found' => 'Transaction not found',
            'Invalid request' => 'Invalid request',
        ]
    ];

    $trimmedKey = trim($key);
    if (isset($messages[$lang][$trimmedKey])) {
        return $messages[$lang][$trimmedKey];
    }
    $lowerKey = strtolower($trimmedKey);
    if (isset($messages[$lang][$lowerKey])) {
        return $messages[$lang][$lowerKey];
    }
    if ($lang !== 'en') {
        if (isset($messages['en'][$trimmedKey])) {
            return $messages['en'][$trimmedKey];
        }
        if (isset($messages['en'][$lowerKey])) {
            return $messages['en'][$lowerKey];
        }
    }

    return $trimmedKey;
}
