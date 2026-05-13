import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <App>
                <div className="sa-error">
                    <div className="sa-error__background-text">
                        Oops! Error 404
                    </div>
                    <div className="sa-error__content">
                        <h1 className="sa-error__title">Page Not Found</h1>
                        <p className="sa-error__text">
                            We can't seem to find the page you're looking for.<br/>
                            Try to use the search.
                        </p>
                        <form className="sa-error__controls">
                            <input type="text" placeholder="Search..." className="form-control form-control--search-filled sa-error__input" />

                            <button type="submit" className="btn btn-primary">Search</button>
                        </form>
                        <p className="sa-error__text">
                            Or go to the home page to start over.
                        </p>
                        <a className="btn btn-secondary btn-sm" href={url('dashboard')}>
                            Go To Home Page
                        </a>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
