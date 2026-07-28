require "test_helper"

class LaravelImporterTest < ActiveSupport::TestCase
  test "fails fast when the source database url is missing" do
    error = assert_raises(ArgumentError) do
      LaravelImporter.new(database_url: nil).run
    end

    assert_equal "SOURCE_DATABASE_URL is required", error.message
  end
end
