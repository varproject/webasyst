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
                    title="Charts"
                    subtitle="The template uses the Chart.js library to draw charts. It provides a simple yet flexible JavaScript charting for designers and developers."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Charts'},
                    ]}
                >
                    <Anchor tag="h2">
                        Line
                    </Anchor>

                    <p>
                        A line chart is a way of plotting data points on a line. Often, it is used to show trend data,
                        or the comparison of two data sets.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-line" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Line Area
                    </Anchor>

                    <p>
                        Line charts support a fill option on the dataset object which can be used to create area between
                        two datasets or a dataset and a boundary.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-line-area" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Vertical Bar
                    </Anchor>

                    <p>
                        A bar chart provides a way of showing data values represented as vertical bars. It is sometimes
                        used to show trend data, and the comparison of multiple data sets side by side.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-vertical-bar" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Horizontal Bar
                    </Anchor>

                    <p>
                        A horizontal bar chart is a variation on a vertical bar chart. It is sometimes used to show
                        trend data, and the comparison of multiple data sets side by side.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-horizontal-bar" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Stacked Bar
                    </Anchor>

                    <p>
                        Bar charts can be configured into stacked bar charts by changing the settings on the X and Y
                        axes to enable stacking. Stacked bar charts can be used to show how one data series is made up
                        of a number of smaller pieces.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-stacked-bar" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Pie
                    </Anchor>

                    <p>
                        Pie chart are probably the most commonly used chart. It is divided into segments, the arc of
                        each segment shows the proportional value of each piece of data.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-pie" className="max-h-20x" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Doughnut
                    </Anchor>

                    <p>
                        Doughnut is another popular type of pie chart.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <canvas id="example-chart-js-doughnut" className="max-h-20x" />
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
