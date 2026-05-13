import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const directions = ['Left', 'Top', 'Bottom', 'Right' ];

    return (
        <Layout>
            <App>
                <Article
                    title="Popovers"
                    subtitle="Documentation and examples for adding Bootstrap popovers, like those found in iOS, to any element on your site."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Popovers'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        A basic popover with title and content.
                    </p>

                    <Example>
                        <button
                            type="button"
                            className="btn btn-primary"
                            data-bs-toggle="popover"
                            title="Popover title"
                            data-bs-content="And here's some amazing content. It's very engaging. Right?"
                        >
                            Click to toggle popover
                        </button>
                    </Example>

                    <Anchor tag="h2">
                        Four directions
                    </Anchor>

                    <p>
                        Four options are available: top, right, bottom, and left aligned. Directions are mirrored when
                        using Bootstrap in RTL.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {directions.map(direction => (
                                <div key={direction} className="col-auto">
                                    <button
                                        type="button"
                                        className="btn btn-secondary"
                                        data-bs-container="body"
                                        data-bs-toggle="popover"
                                        data-bs-placement={direction.toLowerCase()}
                                        data-bs-content={`${direction} popover`}
                                    >
                                        Popover on {direction.toLowerCase()}
                                    </button>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Dismiss on next click
                    </Anchor>

                    <p>
                        Use the <code>focus</code> trigger to dismiss popovers on the user's next click of a different
                        element than the toggle element.
                    </p>

                    <div className="alert alert-info">
                        <h5>Specific markup required for dismiss-on-next-click</h5>
                        <p className="mb-0">
                            For proper cross-browser and cross-platform behavior, you must use
                            the <code>&lt;a&gt;</code> tag, <em>not</em> the <code>&lt;button&gt;</code> tag, and you
                            also must include a <code>tabindex</code> attribute.
                        </p>
                    </div>

                    <Example>
                        <a
                            tabIndex={0}
                            className="btn btn-primary"
                            role="button"
                            data-bs-toggle="popover"
                            data-bs-trigger="focus"
                            title="Dismissible popover"
                            data-bs-content="And here's some amazing content. It's very engaging. Right?"
                        >
                            Dismissible popover
                        </a>
                    </Example>

                    <Anchor tag="h2">
                        Disabled elements
                    </Anchor>

                    <p>
                        Elements with the <code>disabled</code> attribute aren't interactive, meaning users cannot hover
                        or click them to trigger a popover (or tooltip). As a workaround, you'll want to trigger the
                        popover from a wrapper <code>&lt;div&gt;</code> or <code>&lt;span&gt;</code>, ideally made
                        keyboard-focusable using <code>tabindex="0"</code>.
                    </p>

                    <p>
                        For disabled popover triggers, you may also prefer <code>data-bs-trigger="hover focus"</code> so
                        that the popover appears as immediate visual feedback to your users as they may not expect
                        to <em>click</em> on a disabled element.
                    </p>

                    <Example>
                        <span
                            className="d-inline-block"
                            tabIndex={0}
                            data-bs-toggle="popover"
                            data-bs-trigger="hover focus"
                            data-bs-content="Disabled popover"
                        >
                            <button className="btn btn-primary" type="button" disabled>
                                Disabled button
                            </button>
                        </span>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
