<?php
class Translator {
    public function getTranslation($lang) {
        $data = [
            'en' => [
                // Public Pages
                'hero_title'     => 'Fayenor',
                'hero_desc'      => 'Digitizing the Saudi Investment Journey.',
                'about_us'       => 'About Us',
                'about_desc'     => 'Fayenor Company Limited is a premier consultancy based in Unaizah, Al-Qassim. We specialize in navigating the complexities of the Saudi Ministry of Investment (MISA) landscape.',
                'what_we_do'     => 'Our Services',
                'service_1'      => 'MISA License Processing',
                'service_1_desc' => 'End-to-end management of service licenses and investor permits.',
                'service_2'      => 'Digital Tracking',
                'service_2_desc' => 'Real-time monitoring of GOSI, QIWA, Muqeem, and CR milestones.',
                'service_3'      => 'Legal Compliance',
                'service_3_desc' => 'Facilitating Articles of Association (AoA) and Chamber of Commerce registrations.',
                'contact_us'     => 'Get In Touch',
                'email_label'    => 'Email',
                'location_label' => 'Headquarters',
                'location_val'   => 'Unaizah, Al-Qassim, KSA',
                'login'          => 'Access Portal',
                'explore_us'     => 'Get to know us',

                // Dashboard / Sidebar
                'main_menu'         => 'Main Menu',
                'dashboard'      => 'Dashboard',
                'clients'        => 'Clients',
                'contracts'      => 'Contracts',
                'payroll'        => 'Payroll',
                'system'            => 'System',
                'expenses'       => 'Expenses',
                'settings'       => 'Settings',
                'support_chat'   => 'Support Chat',
                'activity_logs'  => 'Activity Logs',
                'user_access'       => 'User Access',
                'logout'         => 'Logout',
                'security'          => 'Security',
                'billing'           => 'Billing & Invoices',
                'client_messages'   => 'Client Messages',
                'contract_template' => 'Contract Template',
                'financial_audit'   => 'Financial Audit',
                'admin_portal'   => 'Admin Portal'
                
            ],
            'ar' => [
                // Public Pages
                'hero_title'     => 'فاينور',
                'hero_desc'      => 'رقمنة رحلة الاستثمار السعودي.',
                'about_us'       => 'من نحن',
                'about_desc'     => 'شركة فاينور المحدودة هي شركة استشارية رائدة مقرها في عنيزة، القصيم. نحن متخصصون في تتبع وتسهيل إجراءات وزارة الاستثمار السعودية (MISA).',
                'what_we_do'     => 'خدماتنا',
                'service_1'      => 'معالجة تراخيص MISA',
                'service_1_desc' => 'إدارة شاملة لتراخيص الخدمات وتصاريح المستثمرين.',
                'service_2'      => 'التتبع الرقمي',
                'service_2_desc' => 'مراقبة فورية لمراحل التأمينات (GOSI)، قوى (QIWA)، ومقيم.',
                'service_3'      => 'الامتثال القانوني',
                'service_3_desc' => 'تسهيل عقود التأسيس (AoA) واشتراكات الغرفة التجارية.',
                'contact_us'     => 'اتصل بنا',
                'email_label'    => 'البريد الإلكتروني',
                'location_label' => 'المقر الرئيسي',
                'location_val'   => 'عنيزة، القصيم، المملكة العربية السعودية',
                'login'          => 'دخول البوابة',
                'explore_us'     => 'تعرف علينا',

                // Dashboard / Sidebar
                'main_menu'         => 'القائمة الرئيسية',
                'dashboard'      => 'لوحة القيادة',
                'clients'        => 'العملاء',
                'contracts'      => 'العقود',
                'payroll'        => 'الرواتب',
                'expenses'       => 'المصاريف',
                'settings'       => 'الإعدادات',
                'system'            => 'النظام',
                'support_chat'   => 'دردشة الدعم',
                'security'          => 'الأمان',
                'client_messages'   => 'رسائل العملاء',
                'user_access'       => 'صلاحيات المستخدمين',
                'activity_logs'  => 'سجلات النشاط',
                'logout'         => 'تسجيل خروج',
                'billing'           => 'الفواتير والمدفوعات',
                'contract_template' => 'نموذج العقد',
                'financial_audit'   => 'التدقيق المالي',
                'admin_portal'   => 'بوابة الإدارة'

            ]
        ];
        return $data[$lang] ?? $data['en'];
    }
}
?>