import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import React from 'react';
import Layout from '../components/Layout';
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();

    return (
        <Layout>
            <div className="min-h-100 p-0 p-sm-6 d-flex align-items-stretch">
                <div className="card w-25x flex-grow-1 flex-sm-grow-0 m-sm-auto">
                    <div className="card-body p-sm-5 m-sm-3 flex-grow-0">
                        <h1 className="mb-0 fs-3">Confirm email address</h1>

                        <div className="alert alert-success alert-sa-has-icon mt-4 mb-4" role="alert">
                            <div className="alert-sa-icon">
                                {svg('feather/check-circle')}
                            </div>
                            <div className="alert-sa-content">
                                A confirmation email was sent to the <strong>stroyka@example.com</strong>.
                            </div>
                        </div>

                        <p className="pt-2">Before proceeding, we must verify the authenticity of your inbox.</p>

                        <p>
                            Check the mailbox! After receiving the email, click on the link provided to confirm the
                            email address.
                        </p>

                        <p className="mb-0 sa-text--sm">
                            Back to <a href={url('auth/sign-in')}>Sign In</a> page.
                        </p>
                    </div>
                </div>
            </div>
        </Layout>
    );
}
