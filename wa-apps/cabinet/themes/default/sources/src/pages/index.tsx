import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import classnames from "classnames";
import Image from "../components/Image";
import Price from "../components/Price";
import PageHeader from "../components/PageHeader";
import WidgetHeader from "../components/WidgetHeader";
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();

    const statusStyle = (status) => (
        {
            'Canceled': 'badge-sa-danger',
            'Pending': 'badge-sa-primary',
            'Completed': 'badge-sa-success',
            'Hold': 'badge-sa-warning',
        }[status]
    );

    const indicatorsData = [
        {
            title: 'Total sells',
            value: '$3799.00',
            delta: '34.7%',
            deltaDirection: 'rise',
            caption: 'Compared to April 2021',
        },
        {
            title: 'Average order value',
            value: '$272.98',
            delta: '12.0%',
            deltaDirection: 'fall',
            caption: 'Compared to April 2021',
        },
        {
            title: 'Total orders',
            value: '578',
            delta: '27.9%',
            deltaDirection: 'rise',
            caption: 'Compared to April 2021',
        },
    ];

    const activeUsersData = [
        {path: '/products/brandix-z4',   users: 15},
        {path: '/categories/drivetrain', users: 11},
        {path: '/categories/monitors',   users: 7},
        {path: '/account/orders',        users: 4},
        {path: '/cart',                  users: 3},
        {path: '/checkout',              users: 3},
        {path: '/pages/about-us',        users: 1},
    ];

    const incomeStatisticsData = [
        {label: 'Jan', value: (10 / 240) * 1200},
        {label: 'Feb', value: (26 / 240) * 1200},
        {label: 'Mar', value: (105 / 240) * 1200},
        {label: 'Apr', value: (57 / 240) * 1200},
        {label: 'May', value: (94 / 240) * 1200},
        {label: 'Jun', value: (26 / 240) * 1200},
        {label: 'Jul', value: (57 / 240) * 1200},
        {label: 'Aug', value: (48 / 240) * 1200},
        {label: 'Sep', value: (142 / 240) * 1200},
        {label: 'Oct', value: (94 / 240) * 1200},
        {label: 'Nov', value: (128 / 240) * 1200},
        {label: 'Dec', value: (222 / 240) * 1200},
    ];

    const recentOrdersData = [
        {
            number: '00745',
            status: 'Pending',
            country: {name: 'Italy', flag: 'vendor/flag-icons/24/IT.png'},
            customer: {name: 'Giordano Bruno', initials: 'GB'},
            date: '2020-11-02',
            total: '$2,742.00',
        },
        {
            number: '00513',
            status: 'Hold',
            country: {name: 'Germany', flag: 'vendor/flag-icons/24/DE.png'},
            customer: {name: 'Hans Weber', initials: 'HW'},
            date: '2020-09-05',
            total: '$204.00',
        },
        {
            number: '00507',
            status: 'Pending',
            country: {name: 'Italy', flag: 'vendor/flag-icons/24/IT.png'},
            customer: {name: 'Andrea Rossi', initials: 'AR'},
            date: '2020-08-21',
            total: '$5,039.00',
        },
        {
            number: '00104',
            status: 'Canceled',
            country: {name: 'USA', flag: 'vendor/flag-icons/24/US.png'},
            customer: {name: 'Richard Feynman', initials: 'RF'},
            date: '2020-06-22',
            total: '$79.00',
        },
        {
            number: '00097',
            status: 'Completed',
            country: {name: 'Columbia', flag: 'vendor/flag-icons/24/CO.png'},
            customer: {name: 'Leonardo Garcia', initials: 'LG'},
            date: '2020-05-09',
            total: '$826.00',
        },
        {
            number: '00082',
            status: 'Completed',
            country: {name: 'Srbija', flag: 'vendor/flag-icons/24/RS.png'},
            customer: {name: 'Nikola Tesla', initials: 'NT'},
            date: '2020-04-27',
            total: '$1,052.00',
        },

        {
            number: '00063',
            status: 'Pending',
            country: {name: 'France', flag: 'vendor/flag-icons/24/FR.png'},
            customer: {name: 'Marie Curie', initials: 'MC'},
            date: '2020-02-09',
            total: '$441.00',
        },


        {
            number: '00012',
            status: 'Completed',
            country: {name: 'Russia', flag: 'vendor/flag-icons/24/RU.png'},
            customer: {name: 'Konstantin Tsiolkovsky', initials: 'KT'},
            date: '2020-01-01',
            total: '$12,961.00',
        },
    ];

    const salesByTrafficSourceData = [
        {label: 'Yandex',    value: 2742, color: '#ffd333', orders: 12},
        {label: 'YouTube',   value: 3272, color: '#e62e2e', orders: 51},
        {label: 'Google',    value: 2303, color: '#3377ff', orders: 4 },
        {label: 'Facebook',  value: 1434, color: '#29cccc', orders: 10},
        {label: 'Instagram', value: 799,  color: '#5dc728', orders: 1 },
    ];

    const recentActivityData = [
        {
            title: 'Yesterday',
            content: 'Phasellus id mattis nulla. Mauris velit nisi, imperdiet vitae sodales in, maximus ut lectus. Vivamus commodo scelerisque lacus, at porttitor dui iaculis id. <a href="#">Curabitur imperdiet ultrices fermentum.</a>',
        },
        {
            title: '5 days ago',
            content: 'Nulla ut ex mollis, volutpat tellus vitae, accumsan ligula. <a href="#">Curabitur imperdiet ultrices fermentum.</a>',
        },
        {
            title: 'March 27',
            content: 'Donec tempor sapien et fringilla facilisis. Nam maximus consectetur diam.',
        },
        {
            title: 'November 30',
            content: 'Many philosophical debates that began in ancient times are still debated today. In one general sense, philosophy is associated with wisdom, intellectual culture and a search for knowledge.',
        },
    ];

    const recentReviewsData = [
        {
            product: 'Wiper Blades Brandix WL2',
            image: 'images/products/product-1.jpg',
            author: 'Ryan Ford',
            rating: 3,
        },
        {
            product: 'Electric Planer Brandix KL370090G 300 Watts',
            image: 'images/products/product-7.jpg',
            author: 'Adam Taylor',
            rating: 4,
        },
        {
            product: 'Water Tap',
            image: 'images/products/product-10.jpg',
            author: 'Jessica Moore',
            rating: 2,
        },
        {
            product: 'Brandix Router Power Tool 2017ERXPK',
            image: 'images/products/product-5.jpg',
            author: 'Helena Garcia',
            rating: 3,
        },
        {
            product: 'Undefined Tool IRadix DPS3000SY 2700 Watts',
            image: 'images/products/product-2.jpg',
            author: 'Ryan Ford',
            rating: 5,
        },
        {
            product: 'Brandix Screwdriver SCREW150',
            image: 'images/products/product-16.jpg',
            author: 'Charlotte Jones',
            rating: 4,
        },
    ];

    return (
        <Layout>
            <App bodyClassName="px-2 px-lg-4">
                <div className="container pb-6">
                    <PageHeader
                        title="Dashboard"
                        actions={[
                            <select key="date" className="form-select me-3">
                                <option selected>7 October, 2021</option>
                            </select>,
                            <a key="export" href="#" className="btn btn-primary">
                                Export
                            </a>,
                        ]}
                    />

                    <div className="row g-4 g-xl-5">
                        {indicatorsData.map((indicator, indicatorIdx) => (
                            <div key={indicatorIdx} className="col-12 col-md-4 d-flex">
                                <div
                                    className="card saw-indicator flex-grow-1"
                                    data-sa-container-query={JSON.stringify({340: 'saw-indicator--size--lg'})}
                                >
                                    <WidgetHeader title={indicator.title} className="saw-indicator__header" />
                                    <div className="saw-indicator__body">
                                        <div className="saw-indicator__value">{indicator.value}</div>
                                        <div className={classnames('saw-indicator__delta', {'saw-indicator__delta--rise': indicator.deltaDirection === 'rise' , 'saw-indicator__delta--fall': indicator.deltaDirection === 'fall'})}>
                                            <div className="saw-indicator__delta-direction">
                                                {svg(indicator.deltaDirection === 'rise' ? 'stroyka/arrow-rise' : 'stroyka/arrow-fall')}
                                            </div>
                                            <div className="saw-indicator__delta-value">{indicator.delta}</div>
                                        </div>
                                        <div className="saw-indicator__caption">{indicator.caption}</div>
                                    </div>
                                </div>
                            </div>
                        ))}

                        <div className="col-12 col-lg-4 col-xxl-3 d-flex">
                            <div
                                className="card flex-grow-1 saw-pulse"
                                data-sa-container-query={JSON.stringify({560: 'saw-pulse--size--lg'})}
                            >
                                <WidgetHeader title="Active users" className="saw-pulse__header" />
                                <div className="saw-pulse__counter">
                                    148
                                </div>
                                <div className="sa-widget-table saw-pulse__table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Active pages</th>
                                                <th className="text-end">Users</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {activeUsersData.map((page, pageIdx) => (
                                                <tr key={pageIdx}>
                                                    <td><a href="#" className="text-reset">{page.path}</a></td>
                                                    <td className="text-end">{page.users}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div className="col-12 col-lg-8 col-xxl-9 d-flex">
                            <div className="card flex-grow-1 saw-chart" data-sa-data={JSON.stringify(incomeStatisticsData)}>
                                <WidgetHeader title="Income statistics" className="saw-chart__header" />
                                <div className="saw-chart__body">
                                    <div className="saw-chart__container">
                                        <canvas />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-12 col-xxl-9 d-flex">
                            <div className="card flex-grow-1 saw-table">
                                <WidgetHeader title="Recent orders" className="saw-table__header" />
                                <div className="saw-table__body sa-widget-table text-nowrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>No.</th>
                                                <th>Status</th>
                                                <th>Co.</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recentOrdersData.map((order, orderIdx) => (
                                                <tr key={orderIdx}>
                                                    <td><a href={url('order')} className="text-reset">#{order.number}</a></td>
                                                    <td>
                                                        <div className="d-flex fs-6">
                                                            <div className={`badge ${statusStyle(order.status)}`}>
                                                                {order.status}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <Image
                                                            src={order.country.flag}
                                                            className="sa-language-icon d-block"
                                                            alt={order.country.name}
                                                            title={order.country.name}
                                                        />
                                                    </td>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <a href={url('customer')} className="sa-symbol sa-symbol--shape--circle sa-symbol--size--md me-3">
                                                                <div className="sa-symbol__text">{order.customer.initials}</div>
                                                            </a>
                                                            <div>
                                                                <a href={url('customer')} className="text-reset">{order.customer.name}</a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{order.date}</td>
                                                    <td>{order.total}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div className="col-12 col-xxl-3 d-flex">
                            <div
                                className="card flex-grow-1 saw-chart-circle"
                                data-sa-data={JSON.stringify(salesByTrafficSourceData)}
                                data-sa-container-query={JSON.stringify({600: 'saw-chart-circle--size--lg'})}
                            >
                                <WidgetHeader title="Sales by traffic source" className="saw-chart-circle__header" />

                                <div className="saw-chart-circle__body">
                                    <div className="saw-chart-circle__container">
                                        <canvas />
                                    </div>
                                </div>

                                <div className="sa-widget-table saw-chart-circle__table">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th className="text-center">Orders</th>
                                                <th className="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {salesByTrafficSourceData.map((source, sourceIdx) => (
                                                <tr key={sourceIdx}>
                                                    <td>
                                                        <div className="d-flex align-items-center">
                                                            <div
                                                                className="saw-chart-circle__symbol"
                                                                style={{'--saw-chart-circle__symbol--color': source.color} as any}
                                                            />
                                                            <div className="ps-2">{source.label}</div>
                                                        </div>
                                                    </td>
                                                    <td className="text-center">{source.orders}</td>
                                                    <td className="text-end"><Price value={source.value} /></td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div className="col-12 col-lg-6 d-flex">
                            <div className="card flex-grow-1">
                                <div className="card-body">
                                    <WidgetHeader title="Recent activity" className="mb-4" />

                                    <div className="sa-timeline mb-n2 pt-2">
                                        <ul className="sa-timeline__list">
                                            {recentActivityData.map((record, recordIdx) => (
                                                <li key={recordIdx} className="sa-timeline__item">
                                                    <div className="sa-timeline__item-title">{record.title}</div>
                                                    <div className="sa-timeline__item-content" dangerouslySetInnerHTML={{__html: record.content}} />
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="col-12 col-lg-6 d-flex">
                            <div className="card flex-grow-1">
                                <div className="card-body">
                                    <WidgetHeader title="Recent reviews" />
                                </div>

                                <ul className="list-group list-group-flush">
                                    {recentReviewsData.map((review, reviewIdx) => (
                                        <li
                                            key={reviewIdx}
                                            className="list-group-item py-2"
                                        >
                                            <div className="d-flex align-items-center py-3">
                                                <a href={url('product')} className="me-4">
                                                    <div className="sa-symbol sa-symbol--shape--rounded sa-symbol--size--lg">
                                                        <Image src={review.image} size={40} />
                                                    </div>
                                                </a>
                                                <div className="d-flex align-items-center flex-grow-1 flex-wrap">
                                                    <div className="col">
                                                        <a href={url('product')} className="text-reset fs-exact-14">{review.product}</a>
                                                        <div className="text-muted fs-exact-13">
                                                            Reviewed by <a href={url('customer')} className="text-reset">{review.author}</a>
                                                        </div>
                                                    </div>
                                                    <div className="col-12 col-sm-auto">
                                                        <div className="sa-rating ms-sm-3 my-2 my-sm-0" style={{'--sa-rating--value': review.rating / 5} as any}>
                                                            <div className="sa-rating__body" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
