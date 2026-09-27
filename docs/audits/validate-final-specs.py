"""Mechanical specification checks; these are not application/runtime tests.

Run from repository root: python3 docs/audits/validate-final-specs.py
"""
from pathlib import Path
import json
import re
import subprocess

ROOT = Path(__file__).resolve().parents[2]
SPEC = ROOT / 'docs/initiate-file'
NAMES = ['prd.md', 'user-stories.md', 'architecture.md', 'database-schema.md', 'nfr.md']
docs = {name: (SPEC/name).read_text(encoding='utf-8') for name in NAMES}
errors = []

def require(condition, detail):
    if not condition:
        errors.append(detail)

def fr_map(text):
    return dict(re.findall(r'^\| (FR-[A-Z]\d{2}) \|.*?\| (M[1-6]) \|$', text, re.M))

frs = fr_map(docs['prd.md'])
baseline = subprocess.run(['git', 'show', 'HEAD:docs/initiate-file/prd.md'], cwd=ROOT,
                          capture_output=True, text=True, check=True).stdout
require(frs == fr_map(baseline), 'FR inventory/milestone differs from HEAD; explicitly review intended change')
require(len(frs) == 72, 'Expected 72 product FRs')

stories = {}
story_fr = set()
story_text, matrix = docs['user-stories.md'].split('## Matriks traceability', 1)
for match in re.finditer(r'^### (US-\d{3}) .*?(?=^### US-|^---|\Z)', story_text, re.M|re.S):
    story, body = match[1], match[0]
    require(story not in stories, f'Duplicate story {story}')
    acs = dict(re.findall(r'^(\d+)\. (.*)$', body, re.M))
    require(list(map(int, acs)) == list(range(1, len(acs)+1)), f'Nonsequential AC: {story}')
    for number, ac in acs.items():
        require(all(word in ac for word in ['**Given**', '**When**', '**Then**']), f'Incomplete GWT: {story} AC{number}')
    stories[story] = acs
    metadata = re.search(r'^FR: (.*)$', body, re.M)
    require(metadata is not None, f'Missing FR metadata: {story}')
    story_fr.update(re.findall(r'FR-[A-Z]\d{2}', metadata[1]))
require(set(frs) <= story_fr, f'FRs absent from story metadata: {set(frs)-story_fr}')

nfr = dict(re.findall(r'^\| ((?:KIN|SEC|ISO|AND|UX|KOM|LOK|PLH|OBS|DAT)-\d{2}) \| (.*?) \|$', docs['nfr.md'], re.M))
require(len(nfr) == len(re.findall(r'^\| (?:KIN|SEC|ISO|AND|UX|KOM|LOK|PLH|OBS|DAT)-\d{2} \|', docs['nfr.md'], re.M)), 'Duplicate NFR ID')
matrix_rows = {}
matrix_ac_refs = {}
for line in matrix.splitlines():
    if not line.startswith('| FR-'):
        continue
    cells = [c.strip() for c in line.strip('|').split('|')]
    require(len(cells)==6, 'Invalid matrix row: '+line)
    fid, milestone, ac_cell, db, service, tests = cells
    require(fid not in matrix_rows, f'Duplicate matrix FR {fid}')
    matrix_rows[fid] = cells
    require(frs.get(fid)==milestone, f'Matrix milestone mismatch: {fid}')
    refs = re.findall(r'(US-\d{3}) AC ([\d,]+)', ac_cell)
    matrix_ac_refs[fid] = {(story, number) for story, nums in refs for number in nums.split(',')}
    require(bool(refs) and bool(db) and bool(service), f'Broken traceability: {fid}')
    for story, nums in refs:
        for number in nums.split(','):
            require(number in stories.get(story, {}), f'Missing AC: {fid}→{story}:{number}')
    tests = re.findall(r'\b[A-Z]{2,3}-\d{2}\b', tests)
    require(bool(tests), f'Missing NFR mapping: {fid}')
    for test in tests:
        require(test in nfr, f'Unknown NFR: {fid}→{test}')
require(set(matrix_rows)==set(frs), 'Traceability matrix does not cover exact FR inventory')

# Qualified fields are mechanically checked against the table contracts.
schema = docs['database-schema.md']
fields = {}
for match in re.finditer(r'^### 2\.\d+ `([a-z_]+)`.*?(?=^### |^## |\Z)', schema, re.M|re.S):
    table, body = match[1], match[0]
    fields[table] = set(re.findall(r'^\| `([a-z_]+)` \|', body, re.M)) | {'id','created_at','updated_at'}
fields['services'] |= fields['master_services'] | {'branch_id'}
fields['users'].add('owner_business_id')
fields['status_histories'] |= {'business_id','transaction_id','status','user_id'}
fields['promo_branches'] |= {'promo_id','branch_id'}
fields['notification_logs'].update({'created_at','updated_at'})
field_ref_count=0
for name, text in docs.items():
    for table, field in re.findall(r'\b([a-z_]+)\.([a-z_]+)\b', text):
        if table in fields:
            field_ref_count+=1
            require(field in fields[table], f'Missing schema field: {name}:{table}.{field}')
    require(not re.search(r'\b(?:TODO|TBD|N/A)\b|\?\?\?|\bX hari\b|nanti ditentukan', text, re.I), f'Unresolved placeholder: {name}')
    require(text.count('```')%2==0, f'Unclosed code fence: {name}')
    for url in re.findall(r'\]\(([^)]+)\)', text):
        if not re.match(r'https?://',url) and not url.startswith('#'):
            require((SPEC/url.split('#')[0]).exists(), f'Broken link: {name}:{url}')

critical = {
    'tenant':'ISO-01', 'branch':'ISO-02', 'developer_privacy':'ISO-03',
    'relation_tenant':'ISO-06', 'public_mutation':'SEC-09', 'public_masking':'SEC-06',
    'lifecycle':'SEC-08', 'payment_balance':'AND-02', 'payment_race':'AND-06',
    'redemption_race':'AND-07', 'ledger_compensation':'AND-16', 'snapshot':'AND-08',
    'financial_edit':'AND-09', 'state_machine':'AND-14', 'merge':'AND-15',
    'input_replay':'AND-18', 'lock_order':'AND-01', 'notification_identity':'AND-04',
    'notification_recovery':'AND-10', 'wa_quota':'AND-11', 'scheduler':'AND-20',
    'demo_suppression':'AND-12', 'fk_retention':'DAT-01', 'demo_purge':'DAT-02',
    'reporting':'AND-24', 'pwa_privacy':'AND-26', 'restore_hold_and_rpo_limit':'AND-27',
    'stale_wa_recipient':'AND-28', 'runtime_dp_toggle':'AND-17',
}
for invariant, test in critical.items():
    require('[Uji]' in nfr.get(test,''), f'Critical invariant lacks [Uji]: {invariant}/{test}')

# Focused final fixes: guard the removed authority and trace each new AC to the
# actual schema/service/NFR. These checks verify documents, not runtime behavior.
require('dp_allowed' not in fields.get('transactions', set()), 'Removed DP snapshot still present in schema')
for name, body in docs.items():
    require('dp_allowed' not in body, f'Obsolete DP snapshot contract/reference: {name}')
require('dp_enabled' in fields.get('business_settings', set()), 'Missing runtime DP setting')
notification_fields = {'tujuan', 'notification_key', 'attempt_count', 'delivery_started_at',
                       'processing_token', 'wa_quota_month', 'created_at'}
require(notification_fields <= fields.get('notification_logs', set()), 'Missing existing recipient/recovery fields')
require('destination' not in fields.get('notification_logs', set()), 'Duplicate destination field; use tujuan')
require('outbound_resume_at' not in set().union(*fields.values()), 'Restore cutoff must remain deployment configuration')

focused_trace = {
    'FR-A06': ('US-206', [1,4,6,8,11,12,13,14,15], 'AND-17',
               ['payments', 'business_settings.dp_enabled'], ['PaymentService']),
    'FR-O07': ('US-206', [1,4,6,8,11,12,13,14,15], 'AND-17',
               ['payments', 'business_settings.dp_enabled'], ['PaymentService']),
    'FR-A13': ('US-213', [5,6,7,8,9], 'AND-28',
               ['notification_logs.tujuan', 'notification_logs.attempt_count', 'notification_logs.delivery_started_at'],
               ['CustomerService', '6.2.1']),
    'FR-A14': ('US-214', [6,7,8,9], 'AND-28',
               ['notification_logs.tujuan', 'notification_logs.processing_token'], ['CustomerMergeService', '6.2.1']),
    'FR-N01': ('US-301', [5], 'AND-27',
               ['notification_logs.created_at', 'transactions.waktu_siap_diambil'], ['NotificationDispatcher', 'arsitektur9']),
    'FR-N02': ('US-302', [10], 'AND-27',
               ['transactions.waktu_siap_diambil'], ['NotificationDispatcher', 'arsitektur9']),
    'FR-N03': ('US-307', [9,10], 'AND-28',
               ['notification_logs.tujuan', 'notification_logs.attempt_count'], ['NotificationDispatcher', '6.2.1']),
    'FR-N06': ('US-308', [10,11,12,13], 'AND-27',
               ['notification_logs', 'jobs'], ['NotificationRecoveryService', 'arsitektur9']),
    'FR-A01': ('US-101', [9], 'AND-27', ['password_reset_tokens'], ['AccountService']),
}
for fid, (story, nums, invariant, db_refs, service_refs) in focused_trace.items():
    row = matrix_rows.get(fid, ['', '', '', '', '', ''])
    expected = {(story, str(number)) for number in nums}
    require(expected <= matrix_ac_refs.get(fid, set()), f'Focused AC missing from trace: {fid}/{story}')
    require(invariant in re.findall(r'\b[A-Z]{2,3}-\d{2}\b', row[5]), f'Focused NFR missing from trace: {fid}/{invariant}')
    require(all(ref in row[3] for ref in db_refs), f'Focused DB contract missing from trace: {fid}')
    require(all(ref in row[4] for ref in service_refs), f'Focused service/architecture missing from trace: {fid}')

# Detect removal of the critical clauses, not just the presence of their NFR IDs.
# Semantic/adversarial review is still required for changes to their wording.
focused_clauses = {
    'prd.md': ['delivery_started_at IS NULL', 'attempt_count == 0', 'attempt_count > 0',
               'recipient_berubah', 'OUTBOUND_RESTORE_HOLD=true', 'outbound_resume_at',
               'window RPO', 'tidak menjamin deduplikasi', 'total_paid == 0',
               'business_settings.dp_enabled', 'partial pertama commit dahulu'],
    'architecture.md': ['never_attempted := delivery_started_at IS NULL AND attempt_count == 0',
                        'recipient_berubah', 'processing_token/start/next_attempt_at',
                        'OUTBOUND_RESTORE_HOLD=true', 'created_at > outbound_resume_at',
                        'transactions.waktu_siap_diambil > outbound_resume_at', 'di luar backup DB',
                        'window RPO', 'business_settings.dp_enabled', 'total_paid = SUM(payments.jumlah)'],
    'nfr.md': ['OUTBOUND_RESTORE_HOLD=true', 'outbound_resume_at', 'window RPO',
               'attempt_count=0', 'attempt_count>0', 'recipient_berubah', 'partial commit dahulu'],
}
for name, clauses in focused_clauses.items():
    for clause in clauses:
        require(clause in docs[name], f'Focused safety clause missing: {name}: {clause}')

# Topological purge contract: every parent is deleted after its child.
purge=['notification_logs','status_histories','payments','loyalty_histories','transaction_items','audit_logs',
       'transactions','promo_branches','promos','loyalty_settings','services','master_services','customers',
       'users','branches','business_settings','businesses']
fk={
    'notification_logs':['transactions','users'], 'status_histories':['transactions','users'],
    'payments':['transactions','users'], 'loyalty_histories':['transactions','customers'],
    'transaction_items':['transactions','services'], 'audit_logs':['users'],
    'transactions':['users','branches','customers','promos'], 'promo_branches':['promos','branches'],
    'loyalty_settings':['master_services'], 'services':['branches'], 'users':['branches'],
}
for table in purge:
    if table!='businesses':
        for parent in fk.get(table,[])+['businesses']:
            require(purge.index(table)<purge.index(parent), f'Purge FK order invalid: {table}→{parent}')
require('→ promo_branches → promos → loyalty_settings' in docs['architecture.md'], 'Architecture purge omits promo step')

diff=subprocess.run(['git','diff','--check'],cwd=ROOT,capture_output=True,text=True)
require(diff.returncode==0, 'git diff --check: '+diff.stdout+diff.stderr)
print(json.dumps({
    'fr_count':len(frs), 'fr_with_story':len(set(frs)&story_fr),
    'fr_with_explicit_ac_and_trace':len(matrix_rows), 'stories':len(stories),
    'acceptance_criteria':sum(map(len,stories.values())), 'nfr_total':len(nfr),
    'nfr_with_uji':sum('[Uji]' in value for value in nfr.values()),
    'critical_invariants_with_uji':len(critical), 'qualified_schema_references_checked':field_ref_count,
    'focused_trace_contracts_checked':len(focused_trace),
    'milestone_unchanged_from_HEAD':frs==fr_map(baseline),
    'git_diff_check':diff.returncode==0, 'errors':errors,
},indent=2,ensure_ascii=False))
raise SystemExit(bool(errors))
