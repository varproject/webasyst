import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const styles = [
        {key: 'primary', title: 'Primary'},
        {key: 'secondary', title: 'Secondary'},
        {key: 'success', title: 'Success'},
        {key: 'danger', title: 'Danger'},
        {key: 'warning', title: 'Warning'},
        {key: 'info', title: 'Info'},
        {key: 'light', title: 'Light'},
        {key: 'dark', title: 'Dark'},
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Dropdowns"
                    subtitle="Toggle contextual overlays for displaying lists of links and more with the Bootstrap dropdown plugin."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Dropdowns'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Wrap the dropdown's toggle (your button or link) and the dropdown menu
                        within <code>.dropdown</code>, or another element that declares <code>position: relative;</code>.
                        Dropdowns can be triggered from <code>&lt;a&gt;</code> or <code>&lt;button&gt;</code> elements
                        to better fit your potential needs. The examples shown here use
                        semantic <code>&lt;ul&gt;</code> elements where appropriate, but custom markup is supported.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <div className="dropdown">
                                    <button
                                        className="btn btn-primary dropdown-toggle"
                                        type="button"
                                        id="example-dropdown-menu-button-1"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Dropdown button
                                    </button>
                                    <ul className="dropdown-menu" aria-labelledby="example-dropdown-menu-button-1">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="dropdown">
                                    <div className="dropdown">
                                        <a
                                            className="btn btn-primary dropdown-toggle"
                                            href="#"
                                            role="button"
                                            id="example-dropdown-menu-link-1"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                            Dropdown link
                                        </a>
                                        <ul className="dropdown-menu" aria-labelledby="example-dropdown-menu-link-1">
                                            <li><a className="dropdown-item" href="#">Action</a></li>
                                            <li><a className="dropdown-item" href="#">Another action</a></li>
                                            <li><a className="dropdown-item" href="#">Something else here</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <p>
                        The best part is you can do this with any button variant, too:
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <div className="dropdown">
                                        <button
                                            className={`btn btn-${style.key} dropdown-toggle`}
                                            type="button"
                                            id={`dropdownMenuButton-${style.key}`}
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                            Button
                                        </button>
                                        <ul
                                            className="dropdown-menu"
                                            aria-labelledby={`dropdownMenuButton-${style.key}`}
                                        >
                                            <li><a className="dropdown-item" href="#">Action</a></li>
                                            <li><a className="dropdown-item" href="#">Another action</a></li>
                                            <li><a className="dropdown-item" href="#">Something else here</a></li>
                                        </ul>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Split Button
                    </Anchor>

                    <p>
                        Similarly, create split button dropdowns with virtually the same markup as single button
                        dropdowns, but with the addition of <code>.dropdown-toggle-split</code> for proper spacing
                        around the dropdown caret.
                    </p>

                    <p>
                        We use this extra class to reduce the horizontal <code>padding</code> on either side of the
                        caret by 25% and remove the <code>margin-left</code> that's added for regular button dropdowns.
                        Those extra changes keep the caret centered in the split button and provide a more appropriately
                        sized hit area next to the main button.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <div className="btn-group">
                                        <button
                                            type="button"
                                            className={`btn btn-${style.key}`}
                                        >
                                            Action
                                        </button>
                                        <button
                                            type="button"
                                            className={`btn btn-${style.key} dropdown-toggle dropdown-toggle-split`}
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                        >
                                            <span className="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul className="dropdown-menu">
                                            <li><a className="dropdown-item" href="#">Action</a></li>
                                            <li><a className="dropdown-item" href="#">Another action</a></li>
                                            <li><a className="dropdown-item" href="#">Something else here</a></li>
                                        </ul>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        Button dropdowns work with buttons of all sizes, including default and split dropdown buttons.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button
                                        className="btn btn-primary btn-lg dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button className="btn btn-primary btn-lg" type="button">
                                        Split Button
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-lg btn-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        <span className="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div className="row g-3 mt-3">
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button
                                        className="btn btn-primary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button className="btn btn-primary" type="button">
                                        Split Button
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        <span className="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div className="row g-3 mt-3">
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button
                                        className="btn btn-primary btn-sm dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button className="btn btn-primary btn-sm" type="button">
                                        Split Button
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        <span className="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Dark Dropdowns
                    </Anchor>

                    <p>
                        Opt into darker dropdowns to match a dark navbar or custom style by
                        adding <code>.dropdown-menu-dark</code> onto an existing <code>.dropdown-menu</code>. No changes
                        are required to the dropdown items.
                    </p>

                    <Example>
                        <div className="dropdown">
                            <button
                                className="btn btn-primary dropdown-toggle"
                                type="button"
                                id="example-dropdown-menu-button-2"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                Button
                            </button>
                            <ul className="dropdown-menu dropdown-menu-dark" aria-labelledby="example-dropdown-menu-button-2">
                                <li><a className="dropdown-item active" href="#">Action</a></li>
                                <li><a className="dropdown-item" href="#">Another action</a></li>
                                <li><a className="dropdown-item" href="#">Something else here</a></li>
                                <li>
                                    <hr className="dropdown-divider" />
                                </li>
                                <li><a className="dropdown-item" href="#">Separated link</a></li>
                            </ul>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Directions
                    </Anchor>

                    <p>
                        Change the display direction of the dropdown menu by
                        adding <code>.dropup</code>, <code>.dropstart</code>, <code>.dropend</code> classes.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <div className="btn-group">
                                    <button
                                        type="button"
                                        className="btn btn-primary dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group dropup">
                                    <button
                                        type="button"
                                        className="btn btn-primary dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div className="row g-3 mt-3">
                            <div className="col-auto">
                                <div className="btn-group dropstart">
                                    <button
                                        type="button"
                                        className="btn btn-primary dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group dropend">
                                    <button
                                        type="button"
                                        className="btn btn-primary dropdown-toggle"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false"
                                    >
                                        Button
                                    </button>
                                    <ul className="dropdown-menu">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Menu Items
                    </Anchor>

                    <p>
                        Historically dropdown menu contents <em>had</em> to be links, but that's no longer the case with
                        v4. Now you can optionally use <code>&lt;button&gt;</code> elements in your dropdowns instead of
                        just <code>&lt;a&gt;</code>s.
                    </p>

                    <Example>
                        <div className="dropdown">
                            <button
                                className="btn btn-primary dropdown-toggle"
                                type="button"
                                id="example-dropdown-menu-button-3"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                Dropdown
                            </button>
                            <ul className="dropdown-menu" aria-labelledby="example-dropdown-menu-button-3">
                                <li>
                                    <button className="dropdown-item" type="button">Action</button>
                                </li>
                                <li>
                                    <button className="dropdown-item" type="button">Another action</button>
                                </li>
                                <li>
                                    <button className="dropdown-item" type="button">Something else here</button>
                                </li>
                            </ul>
                        </div>
                    </Example>

                    <p>
                        You can also create non-interactive dropdown items with <code>.dropdown-item-text</code>. Feel
                        free to style further with custom CSS or text utilities.
                    </p>

                    <Example>
                        <ul className="dropdown-menu max-w-15x">
                            <li><span className="dropdown-item-text">Dropdown item text</span></li>
                            <li><a className="dropdown-item" href="#">Action</a></li>
                            <li><a className="dropdown-item" href="#">Another action</a></li>
                            <li><a className="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                    </Example>

                    <Anchor tag="h3">
                        Active
                    </Anchor>

                    <p>
                        Add <code>.active</code> to items in the dropdown to <strong>style them as active</strong>. To
                        convey the active state to assistive technologies, use the <code>aria-current</code> attribute —
                        using the <code>page</code> value for the current page, or <code>true</code> for the current
                        item in a set.
                    </p>

                    <Example>
                        <ul className="dropdown-menu max-w-15x">
                            <li><a className="dropdown-item" href="#">Regular link</a></li>
                            <li><a className="dropdown-item active" href="#" aria-current="true">Active link</a></li>
                            <li><a className="dropdown-item" href="#">Another link</a></li>
                        </ul>
                    </Example>

                    <Anchor tag="h3">
                        Disabled
                    </Anchor>

                    <p>
                        Add <code>.disabled</code> to items in the dropdown to <strong>style them as disabled</strong>.
                    </p>

                    <Example>
                        <ul className="dropdown-menu max-w-15x">
                            <li><a className="dropdown-item" href="#">Regular link</a></li>
                            <li>
                                <a className="dropdown-item disabled" tabIndex={-1} aria-disabled="true">
                                    Disabled link
                                </a>
                            </li>
                            <li><a className="dropdown-item" href="#">Another link</a></li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Menu Content
                    </Anchor>

                    <Anchor tag="h3">
                        Headers
                    </Anchor>

                    <p>
                        Add a header to label sections of actions in any dropdown menu.
                    </p>

                    <Example>
                        <ul className="dropdown-menu max-w-15x">
                            <li><h6 className="dropdown-header">Dropdown header</h6></li>
                            <li><a className="dropdown-item" href="#">Action</a></li>
                            <li><a className="dropdown-item" href="#">Another action</a></li>
                        </ul>
                    </Example>

                    <Anchor tag="h3">
                        Dividers
                    </Anchor>

                    <p>
                        Separate groups of related menu items with a divider.
                    </p>

                    <Example>
                        <ul className="dropdown-menu max-w-15x">
                            <li><a className="dropdown-item" href="#">Action</a></li>
                            <li><a className="dropdown-item" href="#">Another action</a></li>
                            <li><a className="dropdown-item" href="#">Something else here</a></li>
                            <li>
                                <hr className="dropdown-divider" />
                            </li>
                            <li><a className="dropdown-item" href="#">Separated link</a></li>
                        </ul>
                    </Example>

                    <Anchor tag="h3">
                        Text
                    </Anchor>

                    <p>
                        Place any freeform text within a dropdown menu with text and use <a
                        href="https://getbootstrap.com/docs/5.0/utilities/spacing/">spacing utilities</a>. Note that
                        you'll likely need additional sizing styles to constrain the menu width.
                    </p>

                    <Example>
                        <div className="dropdown-menu p-4 text-muted max-w-20x">
                            <p>
                                Some example text that's free-flowing within the dropdown menu.
                            </p>
                            <p className="mb-0">
                                And this is more example text.
                            </p>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Forms
                    </Anchor>

                    <p>
                        Put a form within a dropdown menu, or make it into a dropdown menu, and use <a
                        href="https://getbootstrap.com/docs/5.0/utilities/spacing/">margin or padding utilities</a> to
                        give it the negative space you require.
                    </p>

                    <Example>
                        <div className="dropdown-menu max-w-25x">
                            <form className="px-5 py-4">
                                <div className="mb-4">
                                    <label htmlFor="exampleDropdownFormEmail1" className="form-label">
                                        Email address
                                    </label>
                                    <input
                                        type="email"
                                        className="form-control"
                                        id="exampleDropdownFormEmail1"
                                        placeholder="email@example.com"
                                    />
                                </div>
                                <div className="mb-4">
                                    <label htmlFor="exampleDropdownFormPassword1" className="form-label">
                                        Password
                                    </label>
                                    <input
                                        type="password"
                                        className="form-control"
                                        id="exampleDropdownFormPassword1"
                                        placeholder="Password"
                                    />
                                </div>
                                <div className="mb-4">
                                    <div className="form-check">
                                        <input type="checkbox" className="form-check-input" id="dropdownCheck" />
                                        <label className="form-check-label" htmlFor="dropdownCheck">
                                            Remember me
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" className="btn btn-primary">Sign in</button>
                            </form>
                            <div className="dropdown-divider" />
                            <a className="dropdown-item px-5" href="#">New around here? Sign up</a>
                            <a className="dropdown-item px-5" href="#">Forgot password?</a>
                        </div>
                    </Example>

                    <Example>
                        <form className="dropdown-menu p-5 max-w-25x">
                            <div className="mb-4">
                                <label htmlFor="exampleDropdownFormEmail2" className="form-label">
                                    Email address
                                </label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="exampleDropdownFormEmail2"
                                    placeholder="email@example.com"
                                />
                            </div>
                            <div className="mb-4">
                                <label htmlFor="exampleDropdownFormPassword2" className="form-label">
                                    Password
                                </label>
                                <input
                                    type="password"
                                    className="form-control"
                                    id="exampleDropdownFormPassword2"
                                    placeholder="Password"
                                />
                            </div>
                            <div className="mb-4">
                                <div className="form-check">
                                    <input type="checkbox" className="form-check-input" id="dropdownCheck2" />
                                    <label className="form-check-label" htmlFor="dropdownCheck2">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <button type="submit" className="btn btn-primary">Sign in</button>
                        </form>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
