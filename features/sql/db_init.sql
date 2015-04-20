UPDATE dc_user SET user_lang = 'en';
UPDATE dc_blog SET blog_name = 'My first blog' WHERE blog_id = 'default';
UPDATE dc_setting SET setting_value = 'en',blog_id = 'default' WHERE setting_ns = 'system' AND setting_id = 'lang';
