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
                    title="Air Datepicker"
                    subtitle="Lightweight customizable cross-browser jQuery datepicker, built with es5 and css-flexbox. Works in all modern desktop and mobile browsers."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Air Datepicker'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Datepicker will automatically initialize on elements with class <code>.datepicker-here</code>,
                        options may be sent via <code>data</code> attributes.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-auto-close="true"
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>

                    <Anchor tag="h2">
                        Display Inline
                    </Anchor>

                    <p>
                        Initialize plugin on non text input element, such as <code>{'<div></div>'}</code>, or pass the
                        parameter <code>{'{'}inline: true{'}'}</code>.
                    </p>

                    <Example>
                        <div className="datepicker-here" data-language="en" />
                    </Example>

                    <Anchor tag="h2">
                        Date Range
                    </Anchor>

                    <p>
                        Use <code>{'{'}range: true{'}'}</code> for choosing range of dates. As dates
                        separator <code>multipleDatesSeparator</code> will be used.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-range="true"
                            data-multiple-dates-separator=" - "
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>


                    <p>
                        For possibility to select same date two times, you should set <code>{'{'}toggleSelected:
                        false{'}'}</code>.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-range="true"
                            data-multiple-dates-separator=" - "
                            data-toggle-selected="false"
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>

                    <Anchor tag="h2">
                        Selecting Multiple Dates
                    </Anchor>

                    <p>
                        Pass parameter <code>{'{'}multipleDates: true{'}'}</code> for selection of multiple dates. If
                        you want to limit the number of selected dates, pass the desired number <code>{'{'}multipleDates:
                        3{'}'}</code>.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-multiple-dates="3"
                            data-multiple-dates-separator=", "
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>

                    <Anchor tag="h2">
                        Month Selection
                    </Anchor>

                    <p>
                        Pass parameter <code>{'{'}minView: 'months'{'}'}</code> and <code>{'{'}view:
                        'months'{'}'}</code> for selection month.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-min-view="months"
                            data-view="months"
                            data-date-format="MM yyyy"
                            data-auto-close="true"
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>

                    <Anchor tag="h2">
                        Year Selection
                    </Anchor>

                    <p>
                        Pass parameter <code>{'{'}minView: 'years'{'}'}</code> and <code>{'{'}view:
                        'years'{'}'}</code> for selection year.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-min-view="years"
                            data-view="years"
                            data-date-format="yyyy"
                            data-auto-close="true"
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>

                    <Anchor tag="h2">
                        With Timepicker
                    </Anchor>

                    <p>
                        To enable timepicker use option <code>{'{'}timepicker: true{'}'}</code> - it will add current
                        time and a couple of range sliders by which one can pick time. By default current user time will
                        be set. This value can be changed by <code>startDate</code> parameter.
                    </p>

                    <Example>
                        <input
                            type="text"
                            className="form-control datepicker-here"
                            data-min-view="years"
                            data-view="years"
                            data-date-format="yyyy"
                            data-auto-close="true"
                            data-language="en"
                            aria-label="Datepicker"
                        />
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
