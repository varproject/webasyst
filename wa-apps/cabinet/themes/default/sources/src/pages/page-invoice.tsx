import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import InvoiceLogo from "../components/InvoiceLogo";

export default function() {
    return (
        <Layout>
            <App>
                <div className="sa-invoice">
                    <div className="sa-invoice__card">
                        <div className="sa-invoice__header">
                            <div className="sa-invoice__meta">
                                <div className="sa-invoice__title">Invoice SA-0747</div>
                                <div className="sa-invoice__meta-items">
                                    <span>Issue date:</span> Oct 19, 2020<br />
                                    <span>Due date:</span> Nov 19, 2020<br />
                                </div>
                            </div>
                            <div className="sa-invoice__logo">
                                <InvoiceLogo />
                            </div>
                        </div>
                        <div className="sa-invoice__sides">
                            <div className="sa-invoice__side">
                                <div className="sa-invoice__side-title">Invoice To</div>
                                <div className="sa-invoice__side-name">Ryan Ford</div>
                                <div className="sa-invoice__side-meta">
                                    Land 4b4f53, MarsGrad, Sun Orbit, 43.3241-85.239<br />
                                    +0 800 306-8265, ryan@example.com
                                </div>
                            </div>

                            <div className="sa-invoice__side">
                                <div className="sa-invoice__side-title">Invoice From</div>
                                <div className="sa-invoice__side-name">Stroyka Ltd.</div>
                                <div className="sa-invoice__side-meta">
                                    715 Fake Street, New York 10021 USA<br />
                                    +0 800 306-8265, stroyka@example.com
                                </div>
                            </div>
                        </div>
                        <div className="sa-invoice__table-container">
                            <table className="sa-invoice__table">
                                <thead className="sa-invoice__table-head">
                                    <tr>
                                        <th className="sa-invoice__table-column--type--product">Product / Service</th>
                                        <th className="sa-invoice__table-column--type--unit">Unit</th>
                                        <th className="sa-invoice__table-column--type--price">Price</th>
                                        <th className="sa-invoice__table-column--type--quantity">Qty</th>
                                        <th className="sa-invoice__table-column--type--total">Total</th>
                                    </tr>
                                </thead>
                                <tbody className="sa-invoice__table-body">
                                    <tr>
                                        <td className="sa-invoice__table-column--type--product">
                                            Screwdriver Brandix ALX7054 200 Watts
                                        </td>
                                        <td className="sa-invoice__table-column--type--unit">Pieces</td>
                                        <td className="sa-invoice__table-column--type--price">$857.00</td>
                                        <td className="sa-invoice__table-column--type--quantity">5</td>
                                        <td className="sa-invoice__table-column--type--total">$3,857.00</td>
                                    </tr>
                                    <tr>
                                        <td className="sa-invoice__table-column--type--product">Water Hose 40cm</td>
                                        <td className="sa-invoice__table-column--type--unit">Pieces</td>
                                        <td className="sa-invoice__table-column--type--price">$54.00</td>
                                        <td className="sa-invoice__table-column--type--quantity">2</td>
                                        <td className="sa-invoice__table-column--type--total">$108.00</td>
                                    </tr>
                                    <tr>
                                        <td className="sa-invoice__table-column--type--product">
                                            Brandix Air Compressor DELTAKX500
                                        </td>
                                        <td className="sa-invoice__table-column--type--unit">Pieces</td>
                                        <td className="sa-invoice__table-column--type--price">$1,800.00</td>
                                        <td className="sa-invoice__table-column--type--quantity">1</td>
                                        <td className="sa-invoice__table-column--type--total">$1,800.00</td>
                                    </tr>
                                    <tr>
                                        <td className="sa-invoice__table-column--type--product">
                                            Adjustment and installation of equipment
                                        </td>
                                        <td className="sa-invoice__table-column--type--unit">Hours</td>
                                        <td className="sa-invoice__table-column--type--price">$89.00</td>
                                        <td className="sa-invoice__table-column--type--quantity">2</td>
                                        <td className="sa-invoice__table-column--type--total">$178.00</td>
                                    </tr>
                                </tbody>
                                <tbody className="sa-invoice__table-totals">
                                    <tr>
                                        <th className="sa-invoice__table-column--type--header" colSpan={4}>Subtotal</th>
                                        <td className="sa-invoice__table-column--type--total">$7,857.00</td>
                                    </tr>
                                    <tr>
                                        <th className="sa-invoice__table-column--type--header" colSpan={4}>Tax (VAT 20%)</th>
                                        <td className="sa-invoice__table-column--type--total">$108.00</td>
                                    </tr>
                                    <tr>
                                        <th className="sa-invoice__table-column--type--header" colSpan={4}>Discount</th>
                                        <td className="sa-invoice__table-column--type--total">-$50.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div className="sa-invoice__total">
                            <div className="sa-invoice__total-title">Total Amount:</div>
                            <div className="sa-invoice__total-value">$7,915.00</div>
                        </div>
                        <div className="sa-invoice__disclaimer">
                            Information on technical characteristics, the delivery set, the country of manufacture and
                            the appearance of the goods is for reference only and is based on the latest information
                            available at the time of publication.
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
