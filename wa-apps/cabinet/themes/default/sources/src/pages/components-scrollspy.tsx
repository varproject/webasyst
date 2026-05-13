import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const text = (
        <>
            This is some placeholder content for the scrollspy page. Note that as you scroll down the page, the
            appropriate navigation link is highlighted. It's repeated throughout the component example. We keep adding
            some more example copy here to emphasize the scrolling and highlighting.
        </>
    );

    return (
        <Layout>
            <App>
                <Article
                    title="Scrollspy"
                    subtitle="Automatically update Bootstrap navigation or list group components based on scroll position to indicate which link is currently active in the viewport."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Scrollspy'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>Scrollspy has a few requirements to function properly:</p>

                    <ul>
                        <li>
                            It must be used on a Bootstrap <a
                            href="https://getbootstrap.com/docs/5.0/components/navs-tabs/">nav component</a> or <a
                            href="https://getbootstrap.com/docs/5.0/components/list-group/">list group</a>.
                        </li>
                        <li>
                            Scrollspy requires <code>position: relative;</code> on the element you're spying on, usually
                            the <code>&lt;body&gt;</code>.
                        </li>
                        <li>
                            Anchors (<code>&lt;a&gt;</code>) are required and must point to an element with
                            that <code>id</code>.
                        </li>
                    </ul>

                    <p>
                        When successfully implemented, your nav or list group will update accordingly, moving
                        the <code>.active</code> class from one item to the next based on their associated targets.
                    </p>

                    <Example>
                        <nav id="scrollspy-example" className="nav nav-pills">
                            <a href="#scrollspy-first" className="nav-link">First</a>
                            <a href="#scrollspy-second" className="nav-link">Second</a>
                            <a href="#scrollspy-third" className="nav-link">Third</a>
                            <a href="#scrollspy-fourth" className="nav-link">Fourth</a>
                        </nav>
                        <div
                            data-bs-spy="scroll"
                            data-bs-target="#scrollspy-example"
                            data-bs-offset="0"
                            className="overflow-auto mt-4 bg-secondary position-relative h-15x"
                            tabIndex={0}
                        >
                            <div id="scrollspy-first" className="p-5 pb-0">
                                <h5>First</h5>
                                <p className="mb-0">{text}</p>
                            </div>
                            <div id="scrollspy-second" className="p-5 pb-0">
                                <h5>Second</h5>
                                <p className="mb-0">{text}</p>
                            </div>
                            <div id="scrollspy-third" className="p-5 pb-0">
                                <h5>Third</h5>
                                <p className="mb-0">{text}</p>
                            </div>
                            <div id="scrollspy-fourth" className="p-5">
                                <h5>Fourth</h5>
                                <p className="mb-0">{text}</p>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
