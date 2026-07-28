# This file is auto-generated from the current state of the database. Instead
# of editing this file, please use the migrations feature of Active Record to
# incrementally modify your database, and then regenerate this schema definition.
#
# This file is the source Rails uses to define your schema when running `bin/rails
# db:schema:load`. When creating a new database, `bin/rails db:schema:load` tends to
# be faster and is potentially less error prone than running all of your
# migrations from scratch. Old migrations may fail to apply correctly if those
# migrations use external dependencies or application code.
#
# It's strongly recommended that you check this file into your version control system.

ActiveRecord::Schema[8.1].define(version: 2026_07_27_000100) do
  create_table "active_storage_attachments", force: :cascade do |t|
    t.integer "blob_id", null: false
    t.datetime "created_at", null: false
    t.string "name", null: false
    t.integer "record_id", null: false
    t.string "record_type", null: false
    t.index ["blob_id"], name: "index_active_storage_attachments_on_blob_id"
    t.index ["record_type", "record_id", "name", "blob_id"], name: "index_active_storage_attachments_uniqueness", unique: true
  end

  create_table "active_storage_blobs", force: :cascade do |t|
    t.bigint "byte_size", null: false
    t.string "checksum"
    t.string "content_type"
    t.datetime "created_at", null: false
    t.string "filename", null: false
    t.string "key", null: false
    t.text "metadata"
    t.string "service_name", null: false
    t.index ["key"], name: "index_active_storage_blobs_on_key", unique: true
  end

  create_table "active_storage_variant_records", force: :cascade do |t|
    t.integer "blob_id", null: false
    t.string "variation_digest", null: false
    t.index ["blob_id", "variation_digest"], name: "index_active_storage_variant_records_uniqueness", unique: true
    t.index ["blob_id"], name: "index_active_storage_variant_records_on_blob_id"
  end

  create_table "circle_post_replies", force: :cascade do |t|
    t.text "body", null: false
    t.integer "circle_post_id", null: false
    t.datetime "created_at", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["circle_post_id"], name: "index_circle_post_replies_on_circle_post_id"
    t.index ["user_id"], name: "index_circle_post_replies_on_user_id"
  end

  create_table "circle_posts", force: :cascade do |t|
    t.text "body", null: false
    t.datetime "created_at", null: false
    t.string "title", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["user_id"], name: "index_circle_posts_on_user_id"
  end

  create_table "daily_logs", force: :cascade do |t|
    t.text "content", null: false
    t.datetime "created_at", null: false
    t.date "log_date", null: false
    t.integer "study_minutes", default: 0, null: false
    t.string "title", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["user_id", "log_date"], name: "index_daily_logs_on_user_id_and_log_date", unique: true
    t.index ["user_id"], name: "index_daily_logs_on_user_id"
  end

  create_table "friendships", force: :cascade do |t|
    t.integer "addressee_id", null: false
    t.datetime "created_at", null: false
    t.integer "requester_id", null: false
    t.string "status", null: false
    t.datetime "updated_at", null: false
    t.index ["addressee_id"], name: "index_friendships_on_addressee_id"
    t.index ["requester_id", "addressee_id"], name: "index_friendships_on_requester_id_and_addressee_id", unique: true
    t.index ["requester_id"], name: "index_friendships_on_requester_id"
  end

  create_table "study_focus_participations", force: :cascade do |t|
    t.datetime "created_at", null: false
    t.integer "duration_seconds", default: 0, null: false
    t.datetime "ended_at"
    t.datetime "paused_at"
    t.integer "paused_seconds", default: 0, null: false
    t.datetime "started_at", null: false
    t.string "status", null: false
    t.integer "study_focus_room_id", null: false
    t.integer "study_session_id"
    t.integer "study_subject_id", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["status"], name: "index_study_focus_participations_on_status"
    t.index ["study_focus_room_id"], name: "index_study_focus_participations_on_study_focus_room_id"
    t.index ["study_session_id"], name: "index_study_focus_participations_on_study_session_id"
    t.index ["study_subject_id"], name: "index_study_focus_participations_on_study_subject_id"
    t.index ["user_id"], name: "index_study_focus_participations_on_user_id"
  end

  create_table "study_focus_rooms", force: :cascade do |t|
    t.datetime "created_at", null: false
    t.text "description"
    t.string "icon"
    t.boolean "is_active", default: true, null: false
    t.string "name", null: false
    t.integer "position", default: 1, null: false
    t.integer "study_group_id", null: false
    t.datetime "updated_at", null: false
    t.index ["study_group_id", "position"], name: "index_study_focus_rooms_on_study_group_id_and_position"
    t.index ["study_group_id"], name: "index_study_focus_rooms_on_study_group_id"
  end

  create_table "study_group_members", force: :cascade do |t|
    t.datetime "created_at", null: false
    t.datetime "joined_at"
    t.string "role", null: false
    t.integer "study_group_id", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["study_group_id", "user_id"], name: "index_study_group_members_on_study_group_id_and_user_id", unique: true
    t.index ["study_group_id"], name: "index_study_group_members_on_study_group_id"
    t.index ["user_id"], name: "index_study_group_members_on_user_id"
  end

  create_table "study_groups", force: :cascade do |t|
    t.string "code", null: false
    t.datetime "created_at", null: false
    t.text "description"
    t.string "name", null: false
    t.integer "owner_id", null: false
    t.string "password_hash"
    t.string "status", null: false
    t.datetime "updated_at", null: false
    t.string "visibility", null: false
    t.index ["code"], name: "index_study_groups_on_code", unique: true
    t.index ["owner_id"], name: "index_study_groups_on_owner_id"
  end

  create_table "study_room_participants", force: :cascade do |t|
    t.datetime "created_at", null: false
    t.datetime "joined_at"
    t.datetime "left_at"
    t.integer "study_room_id", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["study_room_id", "user_id"], name: "index_study_room_participants_on_study_room_id_and_user_id", unique: true
    t.index ["study_room_id"], name: "index_study_room_participants_on_study_room_id"
    t.index ["user_id"], name: "index_study_room_participants_on_user_id"
  end

  create_table "study_rooms", force: :cascade do |t|
    t.string "code", null: false
    t.datetime "created_at", null: false
    t.datetime "ended_at"
    t.string "name", null: false
    t.integer "owner_id", null: false
    t.datetime "started_at"
    t.string "subject"
    t.datetime "updated_at", null: false
    t.string "visibility", null: false
    t.index ["code"], name: "index_study_rooms_on_code", unique: true
    t.index ["owner_id"], name: "index_study_rooms_on_owner_id"
  end

  create_table "study_sessions", force: :cascade do |t|
    t.datetime "created_at", null: false
    t.integer "duration_seconds", default: 0, null: false
    t.datetime "ended_at"
    t.text "notes"
    t.datetime "paused_at"
    t.integer "paused_seconds", default: 0, null: false
    t.bigint "source_id"
    t.string "source_type"
    t.datetime "started_at", null: false
    t.integer "study_focus_room_id"
    t.integer "study_group_id"
    t.integer "study_subject_id"
    t.string "subject", null: false
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["source_id"], name: "index_study_sessions_on_source_id"
    t.index ["source_type"], name: "index_study_sessions_on_source_type"
    t.index ["study_focus_room_id"], name: "index_study_sessions_on_study_focus_room_id"
    t.index ["study_group_id"], name: "index_study_sessions_on_study_group_id"
    t.index ["study_subject_id"], name: "index_study_sessions_on_study_subject_id"
    t.index ["user_id"], name: "index_study_sessions_on_user_id"
  end

  create_table "study_subjects", force: :cascade do |t|
    t.datetime "created_at", null: false
    t.text "description"
    t.integer "goal_minutes"
    t.string "goal_period"
    t.string "name", null: false
    t.string "photo_path"
    t.datetime "updated_at", null: false
    t.integer "user_id", null: false
    t.index ["user_id", "name"], name: "index_study_subjects_on_user_id_and_name", unique: true
    t.index ["user_id"], name: "index_study_subjects_on_user_id"
  end

  create_table "users", force: :cascade do |t|
    t.text "bio"
    t.datetime "created_at", null: false
    t.datetime "deleted_at"
    t.string "display_name"
    t.string "email", null: false
    t.datetime "last_login_at"
    t.string "password_digest", null: false
    t.string "profile_photo_path"
    t.string "profile_title"
    t.text "readme_markdown"
    t.datetime "updated_at", null: false
    t.string "username", null: false
    t.index ["email"], name: "index_users_on_email", unique: true
    t.index ["username"], name: "index_users_on_username", unique: true
  end

  add_foreign_key "active_storage_attachments", "active_storage_blobs", column: "blob_id"
  add_foreign_key "active_storage_variant_records", "active_storage_blobs", column: "blob_id"
  add_foreign_key "circle_post_replies", "circle_posts"
  add_foreign_key "circle_post_replies", "users"
  add_foreign_key "circle_posts", "users"
  add_foreign_key "daily_logs", "users"
  add_foreign_key "friendships", "users", column: "addressee_id"
  add_foreign_key "friendships", "users", column: "requester_id"
  add_foreign_key "study_focus_participations", "study_focus_rooms"
  add_foreign_key "study_focus_participations", "study_sessions"
  add_foreign_key "study_focus_participations", "study_subjects"
  add_foreign_key "study_focus_participations", "users"
  add_foreign_key "study_focus_rooms", "study_groups"
  add_foreign_key "study_group_members", "study_groups"
  add_foreign_key "study_group_members", "users"
  add_foreign_key "study_groups", "users", column: "owner_id"
  add_foreign_key "study_room_participants", "study_rooms"
  add_foreign_key "study_room_participants", "users"
  add_foreign_key "study_rooms", "users", column: "owner_id"
  add_foreign_key "study_sessions", "study_focus_rooms"
  add_foreign_key "study_sessions", "study_groups"
  add_foreign_key "study_sessions", "study_subjects"
  add_foreign_key "study_sessions", "users"
  add_foreign_key "study_subjects", "users"
end
