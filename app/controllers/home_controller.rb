class HomeController < ApplicationController
  def landing
    return redirect_to dashboard_path if authenticated?

    render :landing
  end

  def home
    redirect_to(authenticated? ? dashboard_path : login_path)
  end
end
