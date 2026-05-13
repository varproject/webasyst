import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <App>
                <Article
                    title="Pagination"
                    subtitle="Documentation and examples for showing pagination to indicate a series of related content exists across multiple pages."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Pagination'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        We use a large block of connected links for our pagination, making links hard to miss and easily
                        scalable — all while providing large hit areas. Pagination is built with list HTML elements so
                        screen readers can announce the number of available links. Use a
                        wrapping <code>&lt;nav&gt;</code> element to identify it as a navigation section to screen
                        readers and other assistive technologies.
                    </p>

                    <p>
                        In addition, as pages likely have more than one such navigation section, it's advisable to
                        provide a descriptive <code>aria-label</code> for the <code>&lt;nav&gt;</code> to reflect its
                        purpose. For example, if the pagination component is used to navigate between a set of search
                        results, an appropriate label could be <code>aria-label="Search results pages"</code>.
                    </p>

                    <Example>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination mb-0">
                                <li className="page-item"><a className="page-link" href="#">Previous</a></li>
                                <li className="page-item"><a className="page-link" href="#">1</a></li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item"><a className="page-link" href="#">Next</a></li>
                            </ul>
                        </nav>
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        Fancy larger or smaller pagination?
                        Add <code>.pagination-lg</code> or <code>.pagination-sm</code> for additional sizes.
                    </p>

                    <Example>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-lg">
                                <li className="page-item">
                                    <a className="page-link page-link-sa-prev" href="#" aria-label="Previous" />
                                </li>
                                <li className="page-item active" aria-current="page">
                                    <span className="page-link">1</span>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link page-link-sa-next" href="#" aria-label="Next" />
                                </li>
                            </ul>
                        </nav>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination">
                                <li className="page-item">
                                    <a className="page-link page-link-sa-prev" href="#" aria-label="Previous" />
                                </li>
                                <li className="page-item active" aria-current="page">
                                    <span className="page-link">1</span>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link page-link-sa-next" href="#" aria-label="Next" />
                                </li>
                            </ul>
                        </nav>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-sm mb-0">
                                <li className="page-item">
                                    <a className="page-link page-link-sa-prev" href="#" aria-label="Previous" />
                                </li>
                                <li className="page-item active" aria-current="page">
                                    <span className="page-link">1</span>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link page-link-sa-next" href="#" aria-label="Next" />
                                </li>
                            </ul>
                        </nav>
                    </Example>

                    <Anchor tag="h2">
                        Working With Icons
                    </Anchor>

                    <p>
                        Looking to use an icon in place of text for some pagination links? Be sure to provide proper
                        screen reader support with <code>aria</code> attributes.
                    </p>

                    <Example>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-sm mb-0">
                                <li className="page-item">
                                    <a className="page-link page-link-sa-prev" href="#" aria-label="Previous" />
                                </li>
                                <li className="page-item"><a className="page-link" href="#">1</a></li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link page-link-sa-next" href="#" aria-label="Next" />
                                </li>
                            </ul>
                        </nav>
                    </Example>

                    <Anchor tag="h2">
                        Disabled And Active States
                    </Anchor>

                    <p>
                        Pagination links are customizable for different circumstances. Use <code>.disabled</code> for
                        links that appear un-clickable and <code>.active</code> to indicate the current page.
                    </p>

                    <p>
                        While the <code>.disabled</code> class uses <code>pointer-events: none</code> to <em>try</em> to
                        disable the link functionality of <code>&lt;a&gt;</code>s, that CSS property is not yet
                        standardized and doesn't account for keyboard navigation. As such, you should always
                        add <code>tabindex="-1"</code> on disabled links and use custom JavaScript to fully disable
                        their functionality.
                    </p>

                    <Example>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-sm mb-0">
                                <li className="page-item disabled">
                                    <a className="page-link" tabIndex={-1} aria-disabled="true">Previous</a>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">1</a></li>
                                <li className="page-item active" aria-current="page">
                                    <a className="page-link" href="#">2</a>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </Example>

                    <Anchor tag="h2">
                        Alignment
                    </Anchor>

                    <p>
                        Change the alignment of pagination components with flexbox utilities.
                    </p>

                    <Example>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-sm">
                                <li className="page-item disabled">
                                    <a className="page-link" tabIndex={-1} aria-disabled="true">Previous</a>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">1</a></li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-sm justify-content-center">
                                <li className="page-item disabled">
                                    <a className="page-link" tabIndex={-1} aria-disabled="true">Previous</a>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">1</a></li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <nav aria-label="Page navigation example">
                            <ul className="pagination pagination-sm justify-content-end mb-0">
                                <li className="page-item disabled">
                                    <a className="page-link" tabIndex={-1} aria-disabled="true">Previous</a>
                                </li>
                                <li className="page-item"><a className="page-link" href="#">1</a></li>
                                <li className="page-item"><a className="page-link" href="#">2</a></li>
                                <li className="page-item"><a className="page-link" href="#">3</a></li>
                                <li className="page-item">
                                    <a className="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
