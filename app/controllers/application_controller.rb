class ApplicationController < ActionController::Base
  include Pundit::Authorization

  before_action :set_current_user
  rescue_from Pundit::NotAuthorizedError, with: :user_not_authorized
  helper_method :current_user, :authenticated?

  private

  def set_current_user
    Current.user = session[:user_id].present? ? User.find_by(id: session[:user_id]) : nil
  end

  def current_user
    Current.user
  end

  def authenticated?
    current_user.present?
  end

  def require_authentication
    return if authenticated?

    redirect_to login_path, alert: "Você precisa entrar para continuar."
  end

  def pundit_user
    current_user
  end

  def user_not_authorized
    redirect_back fallback_location: root_path, alert: "Você não tem permissão para acessar essa área."
  end
end
