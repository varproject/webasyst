import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import classNames from "classNames";
import { url } from "../components/utils";

export default function() {
    const tabs = [
        {
            id: 'home-tab',
            title: 'Home',
        },
        {
            id: 'profile-tab',
            title: 'Profile',
        },
        {
            id: 'contact-tab',
            title: 'Contact',
        },
    ];

    const tabsWithLongLink = [
        {
            id: 'home-tab',
            title: 'Home',
        },
        {
            id: 'profile-tab',
            title: 'Profile',
        },
        {
            id: 'much-longer-nav-link-tab',
            title: 'Much longer nav link',
        },
    ];

    const tabsWithDropdownLink = [
        {
            id: 'home-tab',
            title: 'Home',
        },
        {
            id: 'profile-tab',
            title: 'Profile',
        },
        {
            id: 'dropdown-tab',
            title: 'Dropdown',
            children: [
                {
                    id: 'contact-tab',
                    title: 'Contact',
                },
                {
                    id: 'address-tab',
                    title: 'Address',
                },
            ],
        },
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Tabs"
                    subtitle="Use the tab JavaScript plugin to extend navigational tabs and pills to create tabbable panes of local content."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Tabs'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Tabs are one of the most commonly used components for organizing content on a page. To display
                        the current state, inside each <code>.nav-link</code> there must be an empty element with the
                        class <code>.nav-link-sa-indicator</code>.
                    </p>

                    <Example>
                        <ul className="nav nav-tabs" role="tablist">
                            {tabs.map((tab, idx) => (
                                <li key={tab.id} className="nav-item" role="presentation">
                                    <button
                                        className={classNames('nav-link', {active: idx === 0})}
                                        id={`${tab.id}-1`}
                                        data-bs-toggle="tab"
                                        data-bs-target={`#${tab.id}-content-1`}
                                        type="button"
                                        role="tab"
                                        aria-controls={`${tab.id}-content-1`}
                                        aria-selected="true"
                                    >
                                        {tab.title}
                                        <span className="nav-link-sa-indicator" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="tab-content mt-4">
                            {tabs.map((tab, idx) => (
                                <div
                                    key={tab.id}
                                    className={classNames('tab-pane fade', {'show active': idx === 0})}
                                    id={`${tab.id}-content-1`}
                                    role="tabpanel"
                                    aria-labelledby={`${tab.id}-1`}
                                >
                                    <p className="mb-0">
                                        This is some placeholder content the {tab.title} tab's associated
                                        content. Clicking another tab will toggle the visibility of this one for
                                        the next. The tab JavaScript swaps classes to control the content
                                        visibility and styling. You can use it with tabs, pills, and any
                                        other <code>.nav</code>-powered navigation.
                                    </p>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <p>
                        The same example, but inside the card.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-header">
                                <ul className="nav nav-tabs card-header-tabs" role="tablist">
                                    {tabs.map((tab, idx) => (
                                        <li key={tab.id} className="nav-item" role="presentation">
                                            <button
                                                className={classNames('nav-link', {active: idx === 0})}
                                                id={`${tab.id}-2`}
                                                data-bs-toggle="tab"
                                                data-bs-target={`#${tab.id}-content-2`}
                                                type="button"
                                                role="tab"
                                                aria-controls={`${tab.id}-content-2`}
                                                aria-selected="true"
                                            >
                                                {tab.title}
                                                <span className="nav-link-sa-indicator" />
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="card-body">
                                <div className="tab-content">
                                    {tabs.map((tab, idx) => (
                                        <div
                                            key={tab.id}
                                            className={classNames('tab-pane fade', {'show active': idx === 0})}
                                            id={`${tab.id}-content-2`}
                                            role="tabpanel"
                                            aria-labelledby={`${tab.id}-2`}
                                        >
                                            <p className="mb-0">
                                                This is some placeholder content the {tab.title} tab's associated
                                                content. Clicking another tab will toggle the visibility of this one for
                                                the next. The tab JavaScript swaps classes to control the content
                                                visibility and styling. You can use it with tabs, pills, and any
                                                other <code>.nav</code>-powered navigation.
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Fill And Justify
                    </Anchor>

                    <p>
                        Force your <code>.nav</code>'s contents to extend the full available width one of two modifier
                        classes. To proportionately fill all available space with your <code>.nav-item</code>s, use
                        <code>.nav-fill</code>. Notice that all horizontal space is occupied, but not every nav item has the
                        same width.
                    </p>

                    <Example>
                        <ul className="nav nav-tabs nav-fill" role="tablist">
                            {tabsWithLongLink.map((tab, idx) => (
                                <li key={tab.id} className="nav-item" role="presentation">
                                    <button
                                        className={classNames('nav-link', {active: idx === 0})}
                                        id={`${tab.id}-3`}
                                        data-bs-toggle="tab"
                                        data-bs-target={`#${tab.id}-content-3`}
                                        type="button"
                                        role="tab"
                                        aria-controls={`${tab.id}-content-3`}
                                        aria-selected="true"
                                    >
                                        {tab.title}
                                        <span className="nav-link-sa-indicator" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="tab-content mt-4">
                            {tabsWithLongLink.map((tab, idx) => (
                                <div
                                    key={tab.id}
                                    className={classNames('tab-pane fade', {'show active': idx === 0})}
                                    id={`${tab.id}-content-3`}
                                    role="tabpanel"
                                    aria-labelledby={`${tab.id}-3`}
                                >
                                    <p className="mb-0">
                                        This is some placeholder content the {tab.title} tab's associated
                                        content. Clicking another tab will toggle the visibility of this one for
                                        the next. The tab JavaScript swaps classes to control the content
                                        visibility and styling. You can use it with tabs, pills, and any
                                        other <code>.nav</code>-powered navigation.
                                    </p>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <p>
                        For equal-width elements, use <code>.nav-justified</code>. All horizontal space will be occupied by
                        nav links, but unlike the <code>.nav-fill</code> above, every nav item will be the same width.
                    </p>

                    <Example>
                        <ul className="nav nav-tabs nav-justified" role="tablist">
                            {tabsWithLongLink.map((tab, idx) => (
                                <li key={tab.id} className="nav-item" role="presentation">
                                    <button
                                        className={classNames('nav-link', {active: idx === 0})}
                                        id={`${tab.id}-4`}
                                        data-bs-toggle="tab"
                                        data-bs-target={`#${tab.id}-content-4`}
                                        type="button"
                                        role="tab"
                                        aria-controls={`${tab.id}-content-4`}
                                        aria-selected="true"
                                    >
                                        {tab.title}
                                        <span className="nav-link-sa-indicator" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="tab-content mt-5">
                            {tabsWithLongLink.map((tab, idx) => (
                                <div
                                    key={tab.id}
                                    className={classNames('tab-pane fade', {'show active': idx === 0})}
                                    id={`${tab.id}-content-4`}
                                    role="tabpanel"
                                    aria-labelledby={`${tab.id}-4`}
                                >
                                    <p className="mb-0">
                                        This is some placeholder content the {tab.title} tab's associated
                                        content. Clicking another tab will toggle the visibility of this one for
                                        the next. The tab JavaScript swaps classes to control the content
                                        visibility and styling. You can use it with tabs, pills, and any
                                        other <code>.nav</code>-powered navigation.
                                    </p>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        With Dropdowns
                    </Anchor>

                    <p>
                        Add dropdown menus with a little extra HTML and the dropdowns JavaScript plugin.
                    </p>

                    <Example>
                        <ul className="nav nav-tabs" role="tablist">
                            {tabsWithDropdownLink.map((tab, idx) => (
                                <li
                                    key={tab.id}
                                    className={classNames('nav-item', {'dropdown': tab.children?.length > 0})}
                                    role="presentation"
                                >
                                    {!tab.children?.length && (
                                        <button
                                            className={classNames('nav-link', {active: idx === 0})}
                                            id={`${tab.id}-5`}
                                            data-bs-toggle="tab"
                                            data-bs-target={`#${tab.id}-content-5`}
                                            type="button"
                                            role="tab"
                                            aria-controls={`${tab.id}-content-5`}
                                            aria-selected="true"
                                        >
                                            {tab.title}
                                            <span className="nav-link-sa-indicator" />
                                        </button>
                                    )}
                                    {tab.children?.length > 0 && (
                                        <>
                                            <button
                                                className="nav-link dropdown-toggle"
                                                data-bs-toggle="dropdown"
                                                data-bs-offset="0,0"
                                                aria-expanded="false"
                                            >
                                                {tab.title}
                                                <span className="nav-link-sa-indicator" />
                                            </button>
                                            <ul className="dropdown-menu">
                                                {tab.children.map((child, childIdx) => (
                                                    <li key={childIdx}>
                                                        <button
                                                            className="dropdown-item"
                                                            id={`${child.id}-5`}
                                                            data-bs-toggle="tab"
                                                            data-bs-target={`#${child.id}-content-5`}
                                                            type="button"
                                                            role="tab"
                                                            aria-controls={`${child.id}-content-5`}
                                                            aria-selected="false"
                                                        >
                                                            {child.title}
                                                        </button>
                                                    </li>
                                                ))}
                                            </ul>
                                        </>
                                    )}
                                </li>
                            ))}
                        </ul>
                        <div className="tab-content mt-4">
                            {tabsWithDropdownLink.map((tab, idx) => (
                                <React.Fragment key={tab.id}>
                                    {!tab.children?.length && (
                                        <div
                                            className={classNames('tab-pane fade', {'show active': idx === 0})}
                                            id={`${tab.id}-content-5`}
                                            role="tabpanel"
                                            aria-labelledby={`${tab.id}-5`}
                                        >
                                            <p className="mb-0">
                                                This is some placeholder content the {tab.title} tab's associated
                                                content. Clicking another tab will toggle the visibility of this one for
                                                the next. The tab JavaScript swaps classes to control the content
                                                visibility and styling. You can use it with tabs, pills, and any
                                                other <code>.nav</code>-powered navigation.
                                            </p>
                                        </div>
                                    )}
                                    {tab.children?.map((child) => (
                                        <div
                                            key={child.id}
                                            className="tab-pane fade"
                                            id={`${child.id}-content-5`}
                                            role="tabpanel"
                                            aria-labelledby={`${child.id}-5`}
                                        >
                                            <p className="mb-0">
                                                This is some placeholder content the {child.title} tab's associated
                                                content. Clicking another tab will toggle the visibility of this one for
                                                the next. The tab JavaScript swaps classes to control the content
                                                visibility and styling. You can use it with tabs, pills, and any
                                                other <code>.nav</code>-powered navigation.
                                            </p>
                                        </div>
                                    ))}
                                </React.Fragment>
                            ))}
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
