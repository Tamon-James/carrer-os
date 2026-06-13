SET NAMES utf8mb4;
SET time_zone = '+09:00';

ALTER TABLE users
  ADD COLUMN tutorial_status ENUM('pending','active','completed','skipped') NOT NULL DEFAULT 'pending' AFTER password_hash;

ALTER TABLE applications
  ADD COLUMN archived TINYINT(1) NOT NULL DEFAULT 0 AFTER rejected,
  ADD COLUMN title_customized TINYINT(1) NOT NULL DEFAULT 0 AFTER title;

ALTER TABLE flow_steps
  ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER user_id,
  MODIFY application_id BIGINT UNSIGNED NULL,
  ADD CONSTRAINT fk_flow_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE;

UPDATE flow_steps f
JOIN applications a ON a.application_id=f.application_id
SET f.company_id=a.company_id
WHERE f.company_id IS NULL;

ALTER TABLE events
  ADD COLUMN session_type_id BIGINT UNSIGNED NULL AFTER agent_id;

ALTER TABLE interview_drafts
  ADD COLUMN agent_id BIGINT UNSIGNED NULL AFTER application_id,
  ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER agent_id,
  ADD COLUMN session_type_id BIGINT UNSIGNED NULL AFTER event_id,
  ADD CONSTRAINT fk_draft_agent FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_draft_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL;

ALTER TABLE interview_logs
  ADD COLUMN agent_id BIGINT UNSIGNED NULL AFTER application_id,
  ADD COLUMN event_id BIGINT UNSIGNED NULL AFTER agent_id,
  ADD COLUMN session_type_id BIGINT UNSIGNED NULL AFTER event_id,
  ADD COLUMN session_type_label VARCHAR(100) NOT NULL DEFAULT '企業面接' AFTER session_type_id,
  ADD CONSTRAINT fk_log_agent FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_log_event FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE SET NULL;

ALTER TABLE notes
  ADD COLUMN interview_visible TINYINT(1) NOT NULL DEFAULT 0 AFTER body,
  ADD COLUMN note_scope ENUM('linked','common','group') NOT NULL DEFAULT 'linked' AFTER interview_visible,
  ADD COLUMN group_id BIGINT UNSIGNED NULL AFTER note_scope,
  ADD COLUMN deleted_at DATETIME NULL AFTER group_id;

CREATE TABLE note_tags (
  tag_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_note_tag_user_name (user_id,name),
  CONSTRAINT fk_note_tag_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE note_tag_links (
  note_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (note_id,tag_id),
  CONSTRAINT fk_note_tag_link_note FOREIGN KEY (note_id) REFERENCES notes(note_id) ON DELETE CASCADE,
  CONSTRAINT fk_note_tag_link_tag FOREIGN KEY (tag_id) REFERENCES note_tags(tag_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE note_groups (
  group_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  body LONGTEXT NOT NULL,
  archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_note_group_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE notes
  ADD CONSTRAINT fk_note_group FOREIGN KEY (group_id) REFERENCES note_groups(group_id) ON DELETE SET NULL;

CREATE TABLE note_agents (
  note_id BIGINT UNSIGNED NOT NULL,
  agent_id BIGINT UNSIGNED NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (note_id,agent_id),
  CONSTRAINT fk_note_agent_link_note FOREIGN KEY (note_id) REFERENCES notes(note_id) ON DELETE CASCADE,
  CONSTRAINT fk_note_agent_link_agent FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE note_versions (
  version_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  note_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  body LONGTEXT NOT NULL,
  reason VARCHAR(50) NOT NULL DEFAULT 'save',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_note_versions_note (note_id,created_at),
  CONSTRAINT fk_note_version_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_note_version_note FOREIGN KEY (note_id) REFERENCES notes(note_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agent_company_links (
  link_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  agent_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  application_id BIGINT UNSIGNED NULL,
  memo TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_agent_company_application (agent_id,company_id,application_id),
  CONSTRAINT fk_agent_company_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_agent_company_agent FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE,
  CONSTRAINT fk_agent_company_company FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE CASCADE,
  CONSTRAINT fk_agent_company_application FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE session_types (
  session_type_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_session_type_user_name (user_id,name),
  CONSTRAINT fk_session_type_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE state_history (
  history_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(50) NOT NULL,
  summary VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_state_history_entity (user_id,entity_type,entity_id,created_at),
  CONSTRAINT fk_state_history_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutorial_records (
  tutorial_record_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tutorial_records_user (user_id),
  CONSTRAINT fk_tutorial_record_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO note_tags (user_id,name,sort_order)
SELECT user_id,'企業研究',10 FROM users
UNION ALL SELECT user_id,'説明会',20 FROM users
UNION ALL SELECT user_id,'志望動機',30 FROM users
UNION ALL SELECT user_id,'逆質問',40 FROM users
UNION ALL SELECT user_id,'自己PR',50 FROM users
UNION ALL SELECT user_id,'ガクチカ',60 FROM users
UNION ALL SELECT user_id,'面接練習',70 FROM users;

INSERT INTO session_types (user_id,name,sort_order)
SELECT user_id,'企業面接',10 FROM users
UNION ALL SELECT user_id,'エージェント面談',20 FROM users
UNION ALL SELECT user_id,'面接練習',30 FROM users
UNION ALL SELECT user_id,'説明会',40 FROM users
UNION ALL SELECT user_id,'就活相談',50 FROM users;

INSERT INTO notes (user_id,company_id,application_id,category,title,body,interview_visible,note_scope,created_at,updated_at)
SELECT user_id,company_id,application_id,category,title,body,interview_visible,
       CASE WHEN company_id IS NULL THEN 'common' ELSE 'linked' END,created_at,updated_at
FROM contents;

