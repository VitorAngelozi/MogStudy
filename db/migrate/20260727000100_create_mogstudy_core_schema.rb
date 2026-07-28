class CreateMogstudyCoreSchema < ActiveRecord::Migration[8.1]
  def change
    create_table :users do |t|
      t.string :username, null: false
      t.string :display_name
      t.string :profile_title
      t.string :email, null: false
      t.string :password_digest, null: false
      t.text :bio
      t.string :profile_photo_path
      t.text :readme_markdown
      t.datetime :last_login_at
      t.datetime :deleted_at
      t.timestamps
    end
    add_index :users, :username, unique: true
    add_index :users, :email, unique: true

    create_table :study_subjects do |t|
      t.references :user, null: false, foreign_key: true
      t.string :name, null: false
      t.text :description
      t.string :goal_period
      t.integer :goal_minutes
      t.string :photo_path
      t.timestamps
    end
    add_index :study_subjects, [:user_id, :name], unique: true

    create_table :study_sessions do |t|
      t.references :user, null: false, foreign_key: true
      t.references :study_subject, foreign_key: true
      t.references :study_group, foreign_key: true
      t.references :study_focus_room, foreign_key: true
      t.string :subject, null: false
      t.text :notes
      t.string :source_type
      t.bigint :source_id
      t.datetime :started_at, null: false
      t.datetime :ended_at
      t.datetime :paused_at
      t.integer :paused_seconds, default: 0, null: false
      t.integer :duration_seconds, default: 0, null: false
      t.timestamps
    end
    add_index :study_sessions, :source_type
    add_index :study_sessions, :source_id

    create_table :daily_logs do |t|
      t.references :user, null: false, foreign_key: true
      t.date :log_date, null: false
      t.string :title, null: false
      t.text :content, null: false
      t.integer :study_minutes, default: 0, null: false
      t.timestamps
    end
    add_index :daily_logs, [:user_id, :log_date], unique: true

    create_table :friendships do |t|
      t.references :requester, null: false, foreign_key: { to_table: :users }
      t.references :addressee, null: false, foreign_key: { to_table: :users }
      t.string :status, null: false
      t.timestamps
    end
    add_index :friendships, [:requester_id, :addressee_id], unique: true

    create_table :circle_posts do |t|
      t.references :user, null: false, foreign_key: true
      t.string :title, null: false
      t.text :body, null: false
      t.timestamps
    end

    create_table :circle_post_replies do |t|
      t.references :circle_post, null: false, foreign_key: true
      t.references :user, null: false, foreign_key: true
      t.text :body, null: false
      t.timestamps
    end

    create_table :study_groups do |t|
      t.references :owner, null: false, foreign_key: { to_table: :users }
      t.string :name, null: false
      t.string :code, null: false
      t.text :description
      t.string :visibility, null: false
      t.string :password_hash
      t.string :status, null: false
      t.timestamps
    end
    add_index :study_groups, :code, unique: true

    create_table :study_group_members do |t|
      t.references :study_group, null: false, foreign_key: true
      t.references :user, null: false, foreign_key: true
      t.string :role, null: false
      t.datetime :joined_at
      t.timestamps
    end
    add_index :study_group_members, [:study_group_id, :user_id], unique: true

    create_table :study_focus_rooms do |t|
      t.references :study_group, null: false, foreign_key: true
      t.string :name, null: false
      t.text :description
      t.string :icon
      t.integer :position, default: 1, null: false
      t.boolean :is_active, default: true, null: false
      t.timestamps
    end
    add_index :study_focus_rooms, [:study_group_id, :position]

    create_table :study_focus_participations do |t|
      t.references :study_focus_room, null: false, foreign_key: true
      t.references :study_session, foreign_key: true
      t.references :user, null: false, foreign_key: true
      t.references :study_subject, null: false, foreign_key: true
      t.datetime :started_at, null: false
      t.datetime :ended_at
      t.datetime :paused_at
      t.integer :paused_seconds, default: 0, null: false
      t.integer :duration_seconds, default: 0, null: false
      t.string :status, null: false
      t.timestamps
    end
    add_index :study_focus_participations, :status

    create_table :study_rooms do |t|
      t.references :owner, null: false, foreign_key: { to_table: :users }
      t.string :name, null: false
      t.string :subject
      t.string :visibility, null: false
      t.string :code, null: false
      t.datetime :started_at
      t.datetime :ended_at
      t.timestamps
    end
    add_index :study_rooms, :code, unique: true

    create_table :study_room_participants do |t|
      t.references :study_room, null: false, foreign_key: true
      t.references :user, null: false, foreign_key: true
      t.datetime :joined_at
      t.datetime :left_at
      t.timestamps
    end
    add_index :study_room_participants, [:study_room_id, :user_id], unique: true

    create_table :active_storage_blobs do |t|
      t.string   :key,          null: false
      t.string   :filename,     null: false
      t.string   :content_type
      t.text     :metadata
      t.string   :service_name, null: false
      t.bigint   :byte_size,    null: false
      t.string   :checksum
      t.datetime :created_at, precision: 6, null: false
    end
    add_index :active_storage_blobs, :key, unique: true

    create_table :active_storage_attachments do |t|
      t.string     :name,     null: false
      t.references :record,   null: false, polymorphic: true, index: false
      t.references :blob,     null: false, foreign_key: { to_table: :active_storage_blobs }
      t.datetime :created_at, precision: 6, null: false
    end
    add_index :active_storage_attachments, [:record_type, :record_id, :name, :blob_id], unique: true, name: "index_active_storage_attachments_uniqueness"

    create_table :active_storage_variant_records do |t|
      t.references :blob, null: false, foreign_key: { to_table: :active_storage_blobs }
      t.string :variation_digest, null: false
    end
    add_index :active_storage_variant_records, [:blob_id, :variation_digest], unique: true, name: "index_active_storage_variant_records_uniqueness"
  end
end
