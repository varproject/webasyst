import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const styles = [
        {key: 'sa-primary', title: 'Primary'},
        {key: 'sa-secondary', title: 'Secondary'},
        {key: 'sa-success', title: 'Success'},
        {key: 'sa-danger', title: 'Danger'},
        {key: 'sa-warning', title: 'Warning'},
        {key: 'sa-info', title: 'Info'},
        {key: 'sa-light', title: 'Light'},
        {key: 'sa-dark', title: 'Dark'},
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Badges"
                    subtitle="Documentation and examples for badges, our small count and labeling component."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Badges'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Badges scale to match the size of the immediate parent element by using relative font sizing
                        and <code>em</code> units.
                    </p>

                    <Example>
                        <h1>Example heading <span className="badge badge-sa-dark">New</span></h1>
                        <h2>Example heading <span className="badge badge-sa-dark">New</span></h2>
                        <h3>Example heading <span className="badge badge-sa-dark">New</span></h3>
                        <h4>Example heading <span className="badge badge-sa-dark">New</span></h4>
                        <h5>Example heading <span className="badge badge-sa-dark">New</span></h5>
                        <h6>Example heading <span className="badge badge-sa-dark">New</span></h6>
                    </Example>

                    <Anchor tag="h2">
                        Buttons
                    </Anchor>

                    <p>Badges can be used as part of links or buttons to provide a counter.</p>

                    <Example>
                        <button type="button" className="btn btn-primary">
                            Notifications <span className="badge badge-sa-dark">4</span>
                        </button>
                    </Example>

                    <Anchor tag="h2">
                        Positioned
                    </Anchor>

                    <p>
                        Use utilities to modify a <code>.badge</code> and position it in the corner of a link or button.
                    </p>

                    <Example>
                        <button type="button" className="btn btn-primary position-relative">
                            Inbox

                            <span className="position-absolute top-0 start-100 translate-middle badge badge-sa-dark badge-sa-pill">
                                99+ <span className="visually-hidden">unread messages</span>
                            </span>
                        </button>
                    </Example>

                    <p>
                        You can also replace the <code>.badge</code> class with a few more utilities without a count for
                        a more generic indicator.
                    </p>

                    <Example>
                        <button type="button" className="btn btn-primary position-relative">
                            Profile

                            <span className="position-absolute top-0 start-100 translate-middle p-3 badge badge-sa-dark badge-sa-pill">
                                <span className="visually-hidden">New alerts</span>
                            </span>
                        </button>
                    </Example>

                    <Anchor tag="h2">
                        Contextual Variations
                    </Anchor>

                    <p>
                        Add any of the below mentioned modifier classes to change the appearance of a badge.
                    </p>

                    <Example>
                        {styles.map((style, styleIdx) => (
                            <React.Fragment key={styleIdx}>
                                <span className={`badge badge-${style.key}`}>
                                    {style.title}
                                </span>
                                {' '}
                            </React.Fragment>
                        ))}
                    </Example>

                    <Anchor tag="h2">
                        Pill Badges
                    </Anchor>

                    <p>
                        Use the <code>.badge-sa-pill</code> modifier class to make badges more rounded.
                    </p>

                    <Example>
                        {styles.map((style, styleIdx) => (
                            <React.Fragment key={styleIdx}>
                                <span className={`badge badge-sa-pill badge-${style.key}`}>
                                    {style.title}
                                </span>
                                {' '}
                            </React.Fragment>
                        ))}
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
