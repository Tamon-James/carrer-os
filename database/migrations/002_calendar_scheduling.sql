ALTER TABLE events
  ADD COLUMN schedule_status ENUM('confirmed','tentative') NOT NULL DEFAULT 'confirmed' AFTER event_type,
  ADD KEY idx_events_availability (user_id, state, schedule_status, start_at, end_at);

