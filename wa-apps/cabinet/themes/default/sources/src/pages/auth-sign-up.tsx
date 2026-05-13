import React from 'react';
import Layout from '../components/Layout';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <div className="min-h-100 p-0 p-sm-6 d-flex align-items-stretch">
                <div className="card w-25x flex-grow-1 flex-sm-grow-0 m-sm-auto">
                    <div className="card-body p-sm-5 m-sm-3 flex-grow-0">
                        <h1 className="mb-0 fs-3">Sign Up</h1>
                        <div className="fs-exact-14 text-muted mt-2 pt-1 mb-5 pb-2">Fill out the form to create a new account.</div>

                        <div className="mb-4">
                            <label className="form-label">Full name</label>
                            <input type="text" className="form-control form-control-lg" />
                        </div>
                        <div className="mb-4">
                            <label className="form-label">Email Address</label>
                            <input type="email" className="form-control form-control-lg" />
                        </div>
                        <div className="mb-4">
                            <label className="form-label">Password</label>
                            <input type="password" className="form-control form-control-lg" />
                        </div>
                        <div className="mb-4 py-2">
                            <label className="form-check mb-0">
                                <input type="checkbox" className="form-check-input" />
                                <span className="form-check-label">I agree to the <a href={url('terms')}>terms and conditions</a>.</span>
                            </label>
                        </div>
                        <div>
                            <button type="submit" className="btn btn-primary btn-lg w-100">Sign Up</button>
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
                            Already have an account? <a href={url('auth/sign-in')}>Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
