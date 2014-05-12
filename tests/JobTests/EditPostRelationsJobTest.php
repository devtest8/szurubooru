<?php
class EditPostRelationsJobTest extends AbstractTest
{
	public function testEditing()
	{
		$basePost = $this->mockPost($this->mockUser());
		$this->grantAccess('editPostRelations');

		$post1 = $this->mockPost($this->mockUser());
		$post2 = $this->mockPost($this->mockUser());

		$basePost = $this->assert->doesNotThrow(function() use ($basePost, $post1, $post2)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
						$post1->getId(),
						$post2->getId(),
					]
				]);
		});

		$this->assert->areEqual(2, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());
		$this->assert->areEqual($post2->getId(), $basePost->getRelations()[1]->getId());
	}

	public function testOverwriting()
	{
		$basePost = $this->mockPost($this->mockUser());
		$this->grantAccess('editPostRelations');

		$post1 = $this->mockPost($this->mockUser());
		$post2 = $this->mockPost($this->mockUser());

		$basePost->setRelations([$post1]);
		PostModel::save($basePost);

		$this->assert->areEqual(1, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());

		$basePost = $this->assert->doesNotThrow(function() use ($basePost, $post2)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
						$post2->getId(),
					]
				]);
		});

		$this->assert->areEqual(1, count($basePost->getRelations()));
		$this->assert->areEqual($post2->getId(), $basePost->getRelations()[0]->getId());
	}

	public function testOverwritingEmpty()
	{
		$basePost = $this->mockPost($this->mockUser());
		$this->grantAccess('editPostRelations');

		$post1 = $this->mockPost($this->mockUser());
		$post2 = $this->mockPost($this->mockUser());

		$basePost->setRelations([$post1]);
		PostModel::save($basePost);

		$this->assert->areEqual(1, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());

		$basePost = $this->assert->doesNotThrow(function() use ($basePost)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
					]
				]);
		});

		$basePost = PostModel::getById($basePost->getId());
		$this->assert->areEqual(0, count($basePost->getRelations()));
	}
}
