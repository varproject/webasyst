import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";

export default function() {
    const svg = useSvg();

    const sections = [
        {
            title: 'Calendars',
            items: [
                {title: 'Isabel Williams', type: 'checkbox', bgColor: '#DB4343', fontColor: '#fff', checked: true},
                {title: 'Jacob Lee', type: 'checkbox', bgColor: '#F69A2F', fontColor: '#fff', checked: true},
                {title: 'Birthdays', type: 'checkbox', bgColor: '#53a700', fontColor: '#fff', checked: false},
                {title: 'Reminders', type: 'checkbox', bgColor: '#4275C2', fontColor: '#fff', checked: true},
                {title: 'Tasks', type: 'checkbox', bgColor: '#7A42C2', fontColor: '#fff', checked: true},
                {title: 'Family', type: 'checkbox', bgColor: '#C33994', fontColor: '#fff', checked: false},
            ],
        },
        {
            title: 'Today\'s events',
            items: [
                {title: 'Isabel Williams', type: 'circle', bgColor: '#DB4343'},
                {title: 'Tasks', type: 'circle', bgColor: '#F69A2F'},
                {title: 'Jacob Lee', type: 'circle', bgColor: '#53a700'},
                {title: 'Birthdays', type: 'circle', bgColor: '#4275C2'},
                {title: 'Reminders', type: 'circle', bgColor: '#7A42C2'},
                {title: 'Family', type: 'circle', bgColor: '#C33994'},
            ],
        },
    ];

    const sidebar = (
        <React.Fragment>
            <div className="sa-calendar-datepicker" />

            <div className="sa-divider" />

            <div className="sa-nav sa-nav--card sa-nav--card--sm px-3 py-4">
                {sections.map((section, sectionIdx) => (
                    <div key={sectionIdx} className="sa-nav__section">
                        <div className="sa-nav__section-title">{section.title}</div>
                        <ul className="sa-nav__menu">
                            {section.items.map((item, itemIdx) => (
                                <li key={itemIdx} className="sa-nav__menu-item">
                                    {item.type === 'checkbox' && (
                                        <label className="sa-nav__link user-select-none">
                                            <div className="sa-nav__icon sa-color-checkbox" style={{'--sa-color-checkbox--bg-color': item.bgColor, '--sa-color-checkbox--font-color': item.fontColor} as any}>
                                                <input type="checkbox" defaultChecked={item.checked} />

                                                <div className="sa-color-checkbox__box">
                                                    {svg('stroyka/check-14')}
                                                </div>
                                            </div>

                                            <div className="sa-nav__title">{item.title}</div>
                                        </label>
                                    )}

                                    {item.type === 'circle' && (
                                        <a href="#" className="sa-nav__link">
                                            <div className="sa-nav__icon" style={{color: item.bgColor}}>
                                                {svg('stroyka/circle-fill-14')}
                                            </div>

                                            <div className="sa-nav__title">{item.title}</div>
                                        </a>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
        </React.Fragment>
    );

    const content = (
        <div className="card flex-grow-1 mx-sm-0 mx-n4">
            <div id="calendar" className="flex-grow-1" />
        </div>
    );

    return (
        <Layout>
            <App bodyClassName="d-flex flex-column">
                <div className="mx-xxl-3 px-4 px-sm-5">
                    <PageHeader
                        title="Calendar"
                        actions={[
                            <a key="import" href="#" className="btn btn-secondary me-3">
                                Import
                            </a>,
                            <a key="new-event" href="#" className="btn btn-primary">
                                New Event
                            </a>,
                        ]}
                        breadcrumb={[
                            {title: 'Dashboard', url: url('dashboard')},
                            {title: 'Calendar', url: url('calendar')},
                        ]}
                    />
                </div>
                <div className="mx-xxl-3 px-4 px-sm-5 pb-5 mb-3 flex-grow-1 d-flex flex-column">
                    <div className="sa-layout flex-grow-1">
                        <div className="sa-layout__backdrop" data-sa-layout-sidebar-close="" />
                        <div className="sa-layout__sidebar d-flex flex-column">
                            <div className="sa-layout__sidebar-body" data-simplebar="">
                                {sidebar}
                            </div>
                        </div>
                        <div className="sa-layout__content d-flex">
                            {content}
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
