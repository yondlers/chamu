<?php

namespace App\Helpers;

class LeaseTemplates
{
    public const STANDARD_LEASE_TEMPLATE = "

                                <br/>

                                <h1 style='text-align: center;'>
                                    Residential Lease Agreement
                                </h1>

                                <br/>
                                <hr>
                                <br/>

                                <p>This Residential Lease Agreement ('Lease') is entered into on [[TODAY_DATE]] by and between:</p>

                                <p><strong>Landlord:</strong> [[LANDLORD_NAME]], residing at [[LANDLORD_ADDRESS]] (hereinafter referred to as 'Landlord'), and</p>

                                <p><strong>Tenant:</strong> [[TENANT_NAME]], residing at [[TENANT_ADDRESS]] (hereinafter referred to as 'Tenant').</p>

                                <br/><br/>

                                <h2>1. Property</h2>
                                <p>The Landlord hereby leases to the Tenant the premises located at [[PROPERTY_ADDRESS]] (hereinafter referred to as 'the Premises').</p>

                                <br/><br/>

                                <h2>2. Term</h2>
                                <p>The term of this Lease shall begin on [[LEASE_START_DATE]] and shall:</p>
                                <ul>
                                    <li>Be a fixed-term lease ending on [[LEASE_END_DATE]].</li>
                                    <li>Continue on a month-to-month basis until terminated by either party with proper notice.</li>
                                </ul>

                                <br/><br/>

                                <h2>3. Rent</h2>
                                <p>Tenant agrees to pay the Landlord a monthly rent of [[RENT_AMOUNT]], payable in advance on or before the [[RENT_DUE_DATE]] of each month. Rent payments shall be made to:</p>
                                <p><strong>Payment Method:</strong> [[PAYMENT_METHOD]]</p>
                                <p>Late payments will incur a fee of [[LATE_FEE]] if received more than [[LATE_FEE_DAYS]] days after the due date.</p>

                                <br/><br/>

                                <h2>4. Security Deposit</h2>
                                <p>The Tenant shall pay a security deposit of [[DEPOSIT_AMOUNT]] upon signing this Lease. The security deposit shall be held to cover damages beyond normal wear and tear or unpaid rent and shall be returned within 30 days of Lease termination, less any deductions with an itemized statement.</p>

                                <br/><br/>

                                <h2>5. Utilities</h2>
                                <p>The following utilities and services shall be paid as follows:</p>
                                <ul>
                                    <li>Electricity: [[ELECTRICITY_PAYER]]</li>
                                    <li>Water/Sewer: [[WATER_PAYER]]</li>
                                    <li>Gas: [[GAS_PAYER]]</li>
                                    <li>Trash Removal: [[TRASH_PAYER]]</li>
                                    <li>Internet/Cable: [[INTERNET_PAYER]]</li>
                                </ul>

                                <br/><br/>

                                <h2>6. Occupants</h2>
                                <p>The Premises shall be occupied only by the Tenant and the following individuals:</p>
                                <p>[[NUMBER_OF_OCCUPANTS]]</p>
                                <p>Subletting or assigning the Premises is prohibited without prior written consent from the Landlord.</p>

                                <br/><br/>

                                <h2>7. Pets</h2>
                                <p>Pets are not allowed on the Premises.</p>

                                <br/><br/>

                                <h2>8. Maintenance and Repairs</h2>
                                <p>The Tenant shall:</p>
                                <ul>
                                    <li>Keep the Premises clean and in good condition.</li>
                                    <li>Promptly notify the Landlord of any damages or necessary repairs.</li>
                                </ul>
                                <p>The Landlord shall:</p>
                                <ul>
                                    <li>Be responsible for repairs not caused by Tenant negligence.</li>
                                </ul>

                                <br/><br/>

                                <h2>9. Alterations</h2>
                                <p>The Tenant shall not make any alterations, modifications, or improvements to the Premises without the Landlord's prior written consent.</p>

                                <br/><br/>

                                <h2>10. Entry</h2>
                                <p>The Landlord may enter the Premises for inspection, repairs, or emergencies with at least [[NOTICE_PERIOD]] days' notice to the Tenant, except in cases of emergency.</p>

                                <br/><br/>

                                <h2>11. Termination</h2>
                                <p>At the end of the Lease term, the Tenant shall:</p>
                                <ul>
                                    <li>Vacate the Premises, leaving it in clean and rentable condition.</li>
                                    <li>Return all keys and provide a forwarding address for the return of the security deposit.</li>
                                </ul>
                                <p>Termination by either party before the end of the Lease term must comply with applicable state or local laws.</p>

                                <br/><br/>

                                <h2>12. Default</h2>
                                <p>If the Tenant fails to pay rent or violates any terms of this Lease, the Landlord may:</p>
                                <ul>
                                    <li>Serve a notice to cure or vacate.</li>
                                    <li>Pursue eviction proceedings in accordance with applicable law.</li>
                                </ul>

                                <br/><br/>

                                <h2>13. Governing Law</h2>
                                <p>
                                    This Lease Agreement shall be governed by and construed in accordance with the laws of the Republic of South Africa, specifically adhering to the provisions outlined in the Rental Housing Act No. 50 of 1999, as amended, and any applicable municipal bylaws or regulations governing residential lease agreements.
                                </p>
                                <p>
                                    The Landlord and Tenant agree to comply with the rights and obligations as stipulated in the Act, including but not limited to the Tenant's right to a habitable living environment and the Landlord's right to timely payment of rent. Any disputes arising from this Agreement shall be resolved in accordance with the dispute resolution mechanisms provided under the Act, or through mediation or arbitration as mutually agreed upon by the parties.
                                </p>
                                <p>
                                    It is the responsibility of both parties to ensure that all terms of this Lease Agreement comply with South African legislation to avoid any invalidation or penalties as prescribed by law.
                                </p>

                                <br/><br/>

                                <h2>14. Important Notice</h2>
                                <p>
                                    In terms of Section 49 of Act 68 of 2008, the Tenant’s attention is drawn to clauses in this agreement
                                    that have been underlined and may contain:
                                </p>
                                <ul>
                                    <li>Limitations of the risk or liability of the Landlord</li>
                                    <li>Assumption of risk or liability by the Tenant</li>
                                    <li>Indemnifications of the Landlord</li>
                                    <li>Provisions regulating the Tenant’s liability for damages to the Premises</li>
                                    <li>Acknowledgment of the Tenant relating to the state of the Premises at the start and end of the lease</li>
                                </ul>

                                <br/><br/>

                                <h2>15. General Conditions</h2>
                                <p>
                                    The Tenant agrees to:
                                </p>
                                <ul>
                                    <li>Keep the premises in a clean and habitable condition</li>
                                    <li>Report any damages or maintenance issues promptly</li>
                                    <li>Refrain from making unauthorized alterations to the premises</li>
                                    <li>Ensure compliance with all municipal rules and regulations</li>
                                </ul>
                                <p>
                                    The Landlord reserves the right to inspect the premises at reasonable times with prior notice.
                                </p>

                                <br/><br/>

                                <h2>16. Entire Agreement</h2>
                                <p>This Lease constitutes the entire agreement between the Parties and supersedes any prior agreements. Any modifications must be in writing and signed by both Parties.</p>

                                <br/><br/>

                                <h2>17. Signatures</h2>
                                <p>By signing below, both Parties acknowledge that they have read, understood, and agreed to the terms and conditions of this Lease.</p>

                                <br/><br/>

                                <div>
                                    <p><strong>Landlord Signature:</strong></p>
                                    <div style='
                                          position: relative;
                                          left: 0;
                                          bottom: 0;
                                          width: 100%;
                                          text-align: center;'>
                                        [[LANDLORD_SIGNATURE]]
                                    </div>
                                    <hr>
                                    Signed on: [[TODAY_DATE]]
                                </div>

                                <br/><br/>

                                <div>
                                    <p><strong>Tenant Signature:</strong></p>
                                    <div style='
                                          position: relative;
                                          left: 0;
                                          bottom: 0;
                                          width: 100%;
                                          text-align: center;'>
                                        [[TENANT_SIGNATURE]]
                                    </div>
                                    <hr>
                                    Signed on: [[TENANT_SIGNATURE_DATE]]
                                </div>


                                <br/>
                                <br/>

                                <footer
                                    style='
                                      position: relative;
                                      left: 0;
                                      bottom: 0;
                                      width: 100%;
                                      background-color: #101929;
                                      border-radius: 5px;
                                      color: white;
                                      text-align: center;'>
                                    <small>
                                        Powered by <strong>Chamu</strong>
                                    </small>
                                </footer>
    ";
}


