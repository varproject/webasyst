import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import classNames from "classNames";
import { url } from "../components/utils";

export default function() {
    const standardTable = ({table, head}: {table?: string, head?: string} = {table: '', head: ''}) => (
        <table className={classNames('table', table)}>
            <thead className={head}>
            <tr>
                <th scope="col">#</th>
                <th scope="col">First</th>
                <th scope="col">Last</th>
                <th scope="col">Handle</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <th scope="row">1</th>
                <td>Mark</td>
                <td>Otto</td>
                <td>@mdo</td>
            </tr>
            <tr>
                <th scope="row">2</th>
                <td>Jacob</td>
                <td>Thornton</td>
                <td>@fat</td>
            </tr>
            <tr>
                <th scope="row">3</th>
                <td colSpan={2}>Larry the Bird</td>
                <td>@twitter</td>
            </tr>
            </tbody>
        </table>
    );

    return (
        <Layout>
            <App>
                <Article
                    title="Basic Tables"
                    subtitle="Documentation and examples for opt-in styling of tables (given their prevalent use in JavaScript plugins) with Bootstrap."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Tables'},
                        {title: 'Basic Tables'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Using the most basic table markup, here's how <code>.table</code>-based tables look in
                        Bootstrap.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Variants
                    </Anchor>

                    <p>Use contextual classes to color tables, table rows or individual cells.</p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'table-dark mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Striped Rows
                    </Anchor>

                    <p>
                        Use <code>.table-striped</code> to add zebra-striping to any table row within the
                        <code>&lt;tbody&gt;</code>.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'table-striped mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Hoverable Rows
                    </Anchor>

                    <p>
                        Add <code>.table-hover</code> to enable a hover state on table rows within
                        a <code>&lt;tbody&gt;</code>.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'table-hover mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Bordered Tables
                    </Anchor>

                    <p>Add <code>.table-bordered</code> for borders on all sides of the table and cells.</p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'table-bordered mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Tables Without Borders
                    </Anchor>

                    <p>Add <code>.table-borderless</code> for a table without borders.</p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'table-borderless mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Small Tables
                    </Anchor>

                    <p>
                        Add <code>.table-sm</code> to make any <code>.table</code> more compact by cutting all
                        cell <code>padding</code> in half.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'table-sm mb-0'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Nesting
                    </Anchor>

                    <p>Border styles, active styles, and table variants are not inherited by nested tables.</p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <table className="table table-striped table-bordered">
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">1</th>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                    </tr>
                                    <tr>
                                        <td colSpan={4}>
                                            <table className="table mb-0">
                                                <thead>
                                                <tr>
                                                    <th scope="col">Header</th>
                                                    <th scope="col">Header</th>
                                                    <th scope="col">Header</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <th scope="row">A</th>
                                                    <td>First</td>
                                                    <td>Last</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">B</th>
                                                    <td>First</td>
                                                    <td>Last</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">C</th>
                                                    <td>First</td>
                                                    <td>Last</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">3</th>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Anatomy
                    </Anchor>

                    <Anchor tag="h3">
                        Table Head
                    </Anchor>

                    <p>
                        Similar to tables and dark tables, use the modifier classes <code>.table-light</code> or
                        <code>.table-dark</code> to make <code>&lt;thead&gt;</code>s appear light or dark gray.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'mb-0', head: 'table-light'})}
                            </div>
                        </div>
                    </Example>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                {standardTable({table: 'mb-0', head: 'table-dark'})}
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Table Foot
                    </Anchor>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <table className="table mb-0">
                                    <thead className="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">1</th>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">2</th>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">3</th>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                    </tr>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td>Footer</td>
                                        <td>Footer</td>
                                        <td>Footer</td>
                                        <td>Footer</td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h3">
                        Captions
                    </Anchor>

                    <p>
                        A <code>&lt;caption&gt;</code> functions like a heading for a table. It helps users with screen
                        readers to find a table and understand what it's about and decide if they want to read it.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <table className="table mb-0">
                                    <caption className="pb-0">List of users</caption>
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">1</th>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">2</th>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">3</th>
                                        <td colSpan={2}>Larry the Bird</td>
                                        <td>@twitter</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Example>

                    <p>
                        You can also put the <code>&lt;caption&gt;</code> on the top of the table with
                        <code>.caption-top</code>.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <table className="table caption-top mb-0">
                                    <caption className="pt-0">List of users</caption>
                                    <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">First</th>
                                        <th scope="col">Last</th>
                                        <th scope="col">Handle</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <th scope="row">1</th>
                                        <td>Mark</td>
                                        <td>Otto</td>
                                        <td>@mdo</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">2</th>
                                        <td>Jacob</td>
                                        <td>Thornton</td>
                                        <td>@fat</td>
                                    </tr>
                                    <tr>
                                        <th scope="row">3</th>
                                        <td>Larry</td>
                                        <td>the Bird</td>
                                        <td>@twitter</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Responsive Tables
                    </Anchor>

                    <p>
                        Responsive tables allow tables to be scrolled horizontally with ease. Make any table responsive
                        across all viewports by wrapping a <code>.table</code> with <code>.table-responsive</code>. Or,
                        pick a maximum breakpoint with which to have a responsive table up to by
                        using <code>.table-responsive{'-sm|-md|-lg|-xl|-xxl'}</code>.
                    </p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                <div className="table-responsive">
                                    <table className="table">
                                        <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                            <th scope="col">Heading</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                            <td>Cell</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
