ENV["RAILS_ENV"] ||= "test"
require_relative "../config/environment"
require "rails/test_help"
require "json"

module ActiveSupport
  class TestCase
    # Run tests in parallel with specified workers
    parallelize(workers: :number_of_processors, with: :threads)

    # Setup all fixtures in test/fixtures/*.yml for all tests in alphabetical order.
    fixtures :all

    # Add more helper methods to be used by all tests here...
  end
end

module TestAuthHelpers
  def sign_in_as(user, password: "password123")
    post login_path, params: { auth: { email: user.email, password: password } }
  end

  def json_response
    JSON.parse(response.body)
  end
end

class ActiveSupport::TestCase
  include TestAuthHelpers
end
