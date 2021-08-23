--gisapp upgrade script v25

INSERT INTO settings (version, date) VALUES (25, now());

INSERT INTO public.roles VALUES (22, 'user-limit', 'Limited user (viewer, no export)');
