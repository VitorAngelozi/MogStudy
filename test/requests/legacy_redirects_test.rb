require "test_helper"

class LegacyRedirectsTest < ActionDispatch::IntegrationTest
  test "redirects legacy study rooms urls to study groups" do
    get "/study-rooms"
    assert_redirected_to "/study-groups"

    get "/study-rooms/ABC12345"
    assert_redirected_to "/study-groups/ABC12345"
  end
end
