ALTER TABLE notifications ADD COLUMN created_by INT NULL AFTER link;
CREATE INDEX idx_notifications_created_by ON notifications(created_by);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
