import React from 'react';
import Layout from '../components/Layout';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <div className="min-h-100 p-0 p-sm-6 d-flex align-items-stretch">
                <div className="card w-25x flex-grow-1 flex-sm-grow-0 m-sm-auto">
                    <div className="card-body p-sm-5 m-sm-3 flex-grow-0">
                        <h1 className="mb-0 fs-3">Forgot password?</h1>
                        <div className="fs-exact-14 text-muted mt-2 pt-1 mb-5 pb-2">
                            Enter the email address associated with your account and we will send a link to reset your
                            password.
                        </div>

                        <div className="mb-4">
                            <label className="form-label">Email Address</label>
                            <input type="email" className="form-control form-control-lg" />
                        </div>
                        <div>
                            <button type="submit" className="btn btn-primary btn-lg w-100">Reset Password</button>
                        </div>
                        <div className="form-group mb-0 mt-4 pt-2 text-center text-muted">
                            Remember your password? <a href={url('auth/sign-in')}>Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
