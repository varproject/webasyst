import React from 'react';
import Layout from '../components/Layout';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <div className="min-h-100 p-0 p-sm-6 d-flex align-items-stretch">
                <div className="card w-25x flex-grow-1 flex-sm-grow-0 m-sm-auto">
                    <div className="card-body p-sm-5 m-sm-3 flex-grow-0">
                        <h1 className="mb-0 fs-3">Sign In</h1>
                        <div className="fs-exact-14 text-muted mt-2 pt-1 mb-5 pb-2">
                            Log in to your account to continue.
                        </div>

                        <div className="mb-4">
                            <label className="form-label">Email Address</label>
                            <input type="email" className="form-control form-control-lg" />
                        </div>
                        <div className="mb-4">
                            <label className="form-label">Password</label>
                            <input type="password" className="form-control form-control-lg" />
                        </div>
                        <div className="mb-4 row py-2 flex-wrap">
                            <div className="col-auto me-auto">
                                <label className="form-check mb-0">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Remember me</span>
                                </label>
                            </div>
                            <div className="col-auto d-flex align-items-center">
                                <a href={url('auth/forgot-password')}>Forgot password?</a>
                            </div>
                        </div>
                        <div>
                            <button type="submit" className="btn btn-primary btn-lg w-100">Sign In</button>
                        </div>
                    </div>
                    <div className="sa-divider sa-divider--has-text">
                        <div className="sa-divider__text">Or continue with</div>
                    </div>
                    <div className="card-body p-sm-5 m-sm-3 flex-grow-0">
                        <div className="d-flex flex-wrap me-n3 mt-n3">
                            <button type="button" className="btn btn-secondary flex-grow-1 me-3 mt-3">Google</button>
                            <button type="button" className="btn btn-secondary flex-grow-1 me-3 mt-3">Facebook</button>
                            <button type="button" className="btn btn-secondary flex-grow-1 me-3 mt-3">Twitter</button>
                        </div>

                        <div className="form-group mb-0 mt-4 pt-2 text-center text-muted">
                            Don't have an account? <a href={url('auth/sign-up')}>Sign up</a>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
