<?php
$app->get('/apps/membership/detail/{name}', function ($request, $response, $args) {

    $db = $this->getContainer()->get('db');

    $q_member = $db->createQueryBuilder()
    ->select(
        'u.user_id',
        'u.username',
        'u.email',
        'u.created',
        'm.*',
        'reg_prv.regional_name AS province',
        'reg_cit.regional_name AS city'
    )
    ->from('users', 'u')
    ->leftJoin('u', 'members_profiles', 'm', 'u.user_id = m.user_id')
    ->leftJoin('m', 'regionals', 'reg_prv', 'reg_prv.id = m.province_id')
    ->leftJoin('m', 'regionals', 'reg_cit', 'reg_cit.id = m.city_id')
    ->where('u.username = :uname')
    ->setParameter(':rid', 'member')
    ->setParameter(':uname', $args['name'])
    ->execute();

    $member = $q_member->fetch();

    $q_member_socmeds = $db->createQueryBuilder()
    ->select('socmed_type', 'account_name', 'account_url')
    ->from('members_socmeds')
    ->where('user_id = :uid')
    ->andWhere('deleted = :d')
    ->setParameter(':uid', $member['user_id'])
    ->setParameter(':d', 'N')
    ->execute();

    $q_member_portfolios = $db->createQueryBuilder()
    ->select(
        'mp.member_portfolio_id',
        'mp.company_name',
        'ids.industry_name',
        'mp.start_date_y',
        'mp.start_date_m',
        'mp.start_date_d',
        'mp.end_date_y',
        'mp.end_date_m',
        'mp.end_date_d',
        'mp.work_status',
        'mp.job_title',
        'mp.job_desc',
        'mp.created'
    )
    ->from('members_portfolios', 'mp')
    ->leftJoin('mp', 'industries', 'ids', 'mp.industry_id = ids.industry_id')
    ->where('mp.user_id = :uid')
    ->andWhere('mp.deleted = :d')
    ->setParameter(':uid', $member['user_id'])
    ->setParameter(':d', 'N')
    ->execute();

    $q_member_skills = $this->db->createQueryBuilder()
    ->select(
        'ms.member_skill_id',
        'ms.skill_self_assesment',
        'sp.skill_name AS skill_parent_name',
        'ss.skill_name'
    )
    ->from('members_skills', 'ms')
    ->leftJoin('ms', 'skills', 'sp', 'ms.skill_parent_id = sp.skill_id')
    ->leftJoin('ms', 'skills', 'ss', 'ms.skill_id = ss.skill_id')
    ->where('ms.user_id = :uid')
    ->andWhere('ms.deleted = :d')
    ->orderBy('sp.skill_name', 'ASC')
    ->setParameter(':uid', $member['user_id'])
    ->setParameter(':d', 'N')
    ->execute();
    
    $member_portfolios = $q_member_portfolios->fetchAll();
    $member_socmeds = $q_member_socmeds->fetchAll();
    $member_skills = $q_member_skills->fetchAll();
    $socmedias = $this->getContainer()->get('settings')['socmedias'];
    $socmedias_logo = $this->getContainer()->get('settings')['socmedias_logo'];
    $months = $this->getContainer()->get('months');

    /*
     * Data view for skill-add-section
     * //
    */
    $q_skills_main = $this->db->createQueryBuilder()
    ->select('skill_id', 'skill_name')
    ->from('skills')
    ->where('parent_id IS NULL')
    ->execute();

    $skills_main = \Cake\Utility\Hash::combine($q_skills_main->fetchAll(), '{n}.skill_id', '{n}.skill_name');
    $skills = array();

    if (isset($_POST['skill_id']) && $_POST['skill_parent_id'] != '') {
        $q_skills = $this->db->createQueryBuilder()
        ->select('skill_id', 'skill_name')
        ->from('skills')
        ->where('parent_id = :pid')
        ->setParameter(':pid', $_POST['skill_parent_id'])
        ->execute();

        $skills = \Cake\Utility\Hash::combine($q_skills->fetchAll(), '{n}.skill_id', '{n}.skill_name');
    }

    // --- End data view for skill-add-section

    $this->view->getPlates()->addData(
        array(
            'page_title' => 'Membership',
            'sub_page_title' => 'Detail Anggota'
        ),
        'layouts::layout-system'
    );

    $this->view->getPlates()->addData(
        compact('skills_main', 'skills'),
        'membership/sections/skill-add-section'
    );

    return $this->view->render(
        $response,
        'membership/detail',
        compact(
            'member',
            'member_skills',
            'member_socmeds',
            'socmedias',
            'socmedias_logo',
            'member_portfolios',
            'months'
        )
    );

})->setName('membership-detail');


