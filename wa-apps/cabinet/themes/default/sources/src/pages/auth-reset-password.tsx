import React from 'react';
import Layout from '../components/Layout';

export default function() {
    return (
        <Layout>
            <div className="min-h-100 p-0 p-sm-6 d-flex align-items-stretch">
                <div className="card w-25x flex-grow-1 flex-sm-grow-0 m-sm-auto">
                    <div className="card-body p-sm-5 m-sm-3 flex-grow-0">
                        <h1 className="mb-0 fs-3">Reset Password</h1>
                        <div className="fs-exact-14 text-muted mt-2 pt-1 mb-5 pb-2">Please enter your new password.</div>

                        <div className="mb-4">
                            <label className="form-label">Password</label>
                            <input type="password" className="form-control form-control-lg" />
                        </div>
                        <div className="mb-4 pb-2">
                            <label className="form-label">Confirm password</label>
                            <input type="password" className="form-control form-control-lg" />
                        </div>
                        <div className="pt-3">
                            <button type="submit" className="btn btn-primary btn-lg w-100">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
